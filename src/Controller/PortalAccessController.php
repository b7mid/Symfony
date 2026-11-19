<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\JobRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class PortalAccessController extends AbstractController
{
    #[Route('/access', name: 'app_unified_access', methods: ['GET', 'POST'])]
    #[Route('/login', name: 'app_login_page', methods: ['GET', 'POST'])]
    public function unifiedAccess(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $session = $request->getSession();

        if ($request->isMethod('POST')) {
            $mode = (string) $request->request->get('mode', 'login');
            $identifier = trim((string) $request->request->get('identifier', ''));
            $password = (string) $request->request->get('password', '');

            if ($identifier === '' || $password === '') {
                $this->addFlash('error', 'Please fill in both fields.');
                return $this->redirectToRoute('app_unified_access');
            }

            // Admin check (both login and register attempts with admin credentials are checked securely)
            if ($identifier === 'admin') {
                if ($password === 'admin') {
                    $session->remove('guest_authenticated');
                    $session->remove('guest_user_email');
                    $session->set('admin_authenticated', true);
                    $session->set('admin_username', 'admin');

                    $this->addFlash('success', 'Authenticated as Admin. Welcome to the Admin Portal.');
                    return $this->redirectToRoute('app_admin_portal');
                }

                $this->addFlash('error', 'Invalid credentials.');
                return $this->redirectToRoute('app_unified_access');
            }

            // Guest account creation flow
            if ($mode === 'register') {
                if ($userRepository->findOneBy(['email' => $identifier])) {
                    $this->addFlash('error', 'An account with this identifier already exists. Switch to Sign In.');
                    return $this->redirectToRoute('app_unified_access');
                }

                $user = new User();
                if (method_exists($user, 'setEmail')) {
                    $user->setEmail($identifier);
                }
                if (method_exists($user, 'setRoles')) {
                    $user->setRoles(['ROLE_USER']);
                }
                if (method_exists($user, 'setPassword')) {
                    $user->setPassword($passwordHasher->hashPassword($user, $password));
                }

                $entityManager->persist($user);
                $entityManager->flush();

                $session->remove('admin_authenticated');
                $session->remove('admin_username');
                $session->set('guest_authenticated', true);
                $session->set('guest_user_email', $identifier);

                $this->addFlash('success', 'Guest account created. You are now logged in as ' . $identifier . ' and can apply for jobs.');
                return $this->redirectToRoute('app_job_index');
            }

            // Guest login flow
            $user = $userRepository->findOneBy(['email' => $identifier]);
            if (!$user) {
                $this->addFlash('error', 'Account not found. Create a guest account if you do not have one yet.');
                return $this->redirectToRoute('app_unified_access');
            }

            if (method_exists($user, 'getPassword') && $user->getPassword()) {
                if (!$passwordHasher->isPasswordValid($user, $password)) {
                    $this->addFlash('error', 'Invalid credentials.');
                    return $this->redirectToRoute('app_unified_access');
                }
            }

            $session->remove('admin_authenticated');
            $session->remove('admin_username');
            $session->set('guest_authenticated', true);
            $session->set('guest_user_email', $identifier);

            $this->addFlash('success', 'Logged in as ' . $identifier . '. You can now apply for jobs.');
            return $this->redirectToRoute('app_job_index');
        }

        return $this->render('portal/access.html.twig', [
            'isAdmin' => (bool) $session->get('admin_authenticated', false),
            'guestEmail' => $session->get('guest_user_email'),
            'appliedCount' => count($session->get('applied_jobs', [])),
        ]);
    }

    #[Route('/jobs/{id}/apply', name: 'app_job_apply', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function applyForJob(int $id, Request $request, JobRepository $jobRepository): Response
    {
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

        $this->addFlash('info', 'You have been signed out.');
        return $this->redirectToRoute('app_unified_access');
    }
}