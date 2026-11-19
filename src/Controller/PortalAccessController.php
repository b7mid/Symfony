<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\JobRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class PortalAccessController extends AbstractController
{
    #[Route('/access', name: 'app_unified_access', methods: ['GET', 'POST'])]
    #[Route('/login', name: 'app_login_page', methods: ['GET', 'POST'])]
    public function unifiedAccess(
        Request $request,
        UserRepository $userRepository,
        JobRepository $jobRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ContainerBagInterface $params
    ): Response {
        $session = $request->getSession();

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_csrf_token', '');
            if (!$this->isCsrfTokenValid('unified_access_form', $token)) {
                $this->addFlash('error', 'Invalid security token. Please refresh and try again.');
                return $this->redirectToRoute('app_unified_access');
            }

            $failedAttempts = (int) $session->get('auth_failed_attempts', 0);
            $lockUntil = (int) $session->get('auth_lock_until', 0);
            if ($lockUntil > time()) {
                $remaining = $lockUntil - time();
                $this->addFlash('error', 'Too many failed attempts. Please wait ' . $remaining . ' seconds before trying again.');
                return $this->redirectToRoute('app_unified_access');
            }

            $mode = (string) $request->request->get('mode', 'login');
            $identifier = trim((string) $request->request->get('identifier', ''));
            $password = (string) $request->request->get('password', '');

            if ($identifier === '' || $password === '') {
                $this->addFlash('error', 'Please fill in both fields.');
                return $this->redirectToRoute('app_unified_access');
            }

            if (mb_strlen($identifier) > 180 || mb_strlen($password) > 255) {
                $this->addFlash('error', 'Input length exceeds allowed limits.');
                return $this->redirectToRoute('app_unified_access');
            }

            $adminUsername = (string) ($_ENV['APP_ADMIN_USER'] ?? $_SERVER['APP_ADMIN_USER'] ?? 'Ciccada3301');
            $adminPasswordHash = (string) ($_ENV['APP_ADMIN_PASSWORD_HASH'] ?? $_SERVER['APP_ADMIN_PASSWORD_HASH'] ?? '');

            // Admin authentication flow
            if (hash_equals($adminUsername, $identifier)) {
                $validAdminPassword = $adminPasswordHash !== ''
                    ? password_verify($password, $adminPasswordHash)
                    : hash_equals('Ciccada3301@', $password);

                if ($validAdminPassword) {
                    $session->migrate(true);
                    $session->remove('auth_failed_attempts');
                    $session->remove('auth_lock_until');
                    $session->remove('guest_authenticated');
                    $session->remove('guest_user_email');
                    $session->set('admin_authenticated', true);
                    $session->set('admin_username', $adminUsername);

                    $this->addFlash('success', 'Authenticated as ' . $adminUsername . '.');
                    return $this->redirectToRoute('app_admin_portal');
                }

                $this->recordFailedAttempt($session, $failedAttempts);
                $this->addFlash('error', 'Invalid credentials.');
                return $this->redirectToRoute('app_unified_access');
            }

            // Guest account creation flow
            if ($mode === 'register') {
                if (!filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                    $this->addFlash('error', 'Please provide a valid email address for a guest account.');
                    return $this->redirectToRoute('app_unified_access');
                }

                $normalizedEmail = mb_strtolower($identifier);

                if (strlen($password) < 8) {
                    $this->addFlash('error', 'Choose a password with at least 8 characters.');
                    return $this->redirectToRoute('app_unified_access');
                }

                if ($userRepository->findOneBy(['email' => $normalizedEmail])) {
                    $this->addFlash('error', 'An account with this email already exists. Switch to Log In.');
                    return $this->redirectToRoute('app_unified_access');
                }

                $user = new User();
                if (method_exists($user, 'setEmail')) {
                    $user->setEmail($normalizedEmail);
                }
                if (method_exists($user, 'setRoles')) {
                    $user->setRoles(['ROLE_USER']);
                }
                if (method_exists($user, 'setPassword')) {
                    $user->setPassword($passwordHasher->hashPassword($user, $password));
                }

                $entityManager->persist($user);
                $entityManager->flush();

                $session->migrate(true);
                $session->remove('auth_failed_attempts');
                $session->remove('auth_lock_until');
                $session->remove('admin_authenticated');
                $session->remove('admin_username');
                $session->set('guest_authenticated', true);
                $session->set('guest_user_email', $normalizedEmail);

                $this->addFlash('success', 'Guest account created. Signed in as ' . $normalizedEmail . '.');
                return $this->redirectToRoute('app_job_index');
            }

            // Guest login flow
            $lookupEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? mb_strtolower($identifier) : $identifier;
            $user = $userRepository->findOneBy(['email' => $lookupEmail]);
            if (!$user) {
                $this->recordFailedAttempt($session, $failedAttempts);
                $this->addFlash('error', 'Account not found. Create a guest account if you do not have one yet.');
                return $this->redirectToRoute('app_unified_access');
            }

            if (method_exists($user, 'getPassword') && $user->getPassword()) {
                if (!$passwordHasher->isPasswordValid($user, $password)) {
                    $this->recordFailedAttempt($session, $failedAttempts);
                    $this->addFlash('error', 'Invalid credentials.');
                    return $this->redirectToRoute('app_unified_access');
                }
            }

            $session->migrate(true);
            $session->remove('auth_failed_attempts');
            $session->remove('auth_lock_until');
            $session->remove('admin_authenticated');
            $session->remove('admin_username');
            $session->set('guest_authenticated', true);
            $session->set('guest_user_email', $lookupEmail);

            $this->addFlash('success', 'Logged in as ' . $lookupEmail . '.');
            return $this->redirectToRoute('app_job_index');
        }

        $appliedIds = $session->get('applied_jobs', []);
        $appliedJobsList = [];
        foreach ($appliedIds as $jobId) {
            $foundJob = $jobRepository->find((int) $jobId);
            if ($foundJob) {
                $appliedJobsList[] = $foundJob;
            }
        }

        return $this->render('portal/access.html.twig', [
            'isAdmin' => (bool) $session->get('admin_authenticated', false),
            'adminUsername' => $session->get('admin_username'),
            'guestEmail' => $session->get('guest_user_email'),
            'appliedCount' => count($appliedJobsList),
            'appliedJobsList' => $appliedJobsList,
        ]);
    }

    #[Route('/jobs/{id}/apply', name: 'app_job_apply', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function applyForJob(int $id, Request $request, JobRepository $jobRepository): Response
    {
        $token = (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('apply_job_' . $id, $token)) {
            $this->addFlash('error', 'Invalid application token. Please try again.');
            return $this->redirectToRoute('app_job_show', ['id' => $id]);
        }

        $session = $request->getSession();
        $isGuest = (bool) $session->get('guest_authenticated', false);
        $guestEmail = (string) $session->get('guest_user_email', '');

        if (!$isGuest || $guestEmail === '') {
            $this->addFlash('error', 'Please log in or create a guest account first to apply for a job.');
            return $this->redirectToRoute('app_unified_access');
        }

        $job = $jobRepository->find($id);
        if (!$job) {
            throw $this->createNotFoundException('Job offer not found.');
        }

        $appliedJobs = $session->get('applied_jobs', []);
        $jobTitle = method_exists($job, 'getTitle') && $job->getTitle() ? $job->getTitle() : ('Job #' . $id);

        if (!in_array($id, $appliedJobs, true)) {
            $appliedJobs[] = $id;
            $session->set('applied_jobs', $appliedJobs);
            $this->addFlash('success', 'Application submitted by ' . $guestEmail . ' for "' . $jobTitle . '".');
        } else {
            $this->addFlash('info', 'You have already applied for "' . $jobTitle . '" with ' . $guestEmail . '.');
        }

        return $this->redirectToRoute('app_job_show', ['id' => $id]);
    }

    #[Route('/admin', name: 'app_admin_portal', methods: ['GET'])]
    public function adminPortal(Request $request, UserRepository $userRepository): Response
    {
        if (!$request->getSession()->get('admin_authenticated', false)) {
            $this->addFlash('error', 'Admin login required to access the Admin Portal and Candidate Listing.');
            return $this->redirectToRoute('app_unified_access');
        }

        return $this->render('portal/admin.html.twig', [
            'isAdmin' => true,
            'candidates' => $userRepository->findAll(),
        ]);
    }

    #[Route('/admin/candidates', name: 'app_admin_candidates', methods: ['GET'])]
    public function adminCandidates(Request $request, UserRepository $userRepository): Response
    {
        if (!$request->getSession()->get('admin_authenticated', false)) {
            $this->addFlash('error', 'Admin login required to view Candidate Listing.');
            return $this->redirectToRoute('app_unified_access');
        }

        return $this->render('candidate/index.html.twig', [
            'candidates' => $userRepository->findAll(),
        ]);
    }

    #[Route('/logout', name: 'app_portal_logout', methods: ['GET', 'POST'])]
    public function logout(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('admin_authenticated');
        $session->remove('admin_username');
        $session->remove('guest_authenticated');
        $session->remove('guest_user_email');
        $session->migrate(true);

        $this->addFlash('info', 'You have been signed out.');
        return $this->redirectToRoute('app_unified_access');
    }

    private function recordFailedAttempt(SessionInterface $session, int $currentAttempts): void
    {
        $nextAttempts = $currentAttempts + 1;
        $session->set('auth_failed_attempts', $nextAttempts);
        if ($nextAttempts >= 5) {
            $session->set('auth_lock_until', time() + 30);
            $session->set('auth_failed_attempts', 0);
        }
    }
}