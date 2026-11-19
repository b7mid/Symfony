<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\JobRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class PortalAccessController extends AbstractController
{
    #[Route('/guest', name: 'app_guest_portal', methods: ['GET'])]
    public function guestPortal(Request $request): Response
    {
        $session = $request->getSession();
        $guestEmail = $session->get('guest_user_email');
        $appliedJobs = $session->get('applied_jobs', []);

        return $this->render('portal/guest.html.twig', [
            'guestEmail' => $guestEmail,
            'appliedJobs' => $appliedJobs,
        ]);
    }

    #[Route('/guest/register', name: 'app_guest_register', methods: ['POST'])]
    public function guestRegister(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $session = $request->getSession();
        $email = trim((string) $request->request->get('email', ''));
        $password = (string) $request->request->get('password', '');

        if ($email === '' || $password === '') {
            $this->addFlash('error', 'Please enter both an email and a password to create a guest account.');
            return $this->redirectToRoute('app_guest_portal');
        }

        if ($userRepository->findOneBy(['email' => $email])) {
            $this->addFlash('error', 'An account with this email already exists. Please log in instead.');
            return $this->redirectToRoute('app_guest_portal');
        }

        $user = new User();
        if (method_exists($user, 'setEmail')) {
            $user->setEmail($email);
        }
        if (method_exists($user, 'setRoles')) {
            $user->setRoles(['ROLE_USER']);
        }
        if (method_exists($user, 'setPassword')) {
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $session->set('guest_user_email', $email);
        $session->set('guest_authenticated', true);

        $this->addFlash('success', 'Guest account created and logged in as ' . $email . '. You can now apply for jobs!');
        return $this->redirectToRoute('app_job_index');
    }

    #[Route('/guest/login', name: 'app_guest_login', methods: ['POST'])]
    public function guestLogin(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $session = $request->getSession();
        $email = trim((string) $request->request->get('email', ''));
        $password = (string) $request->request->get('password', '');

        if ($email === '' || $password === '') {
            $this->addFlash('error', 'Email and password are required to log in.');
            return $this->redirectToRoute('app_guest_portal');
        }

        $user = $userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $this->addFlash('error', 'Guest account not found. Create an account first.');
            return $this->redirectToRoute('app_guest_portal');
        }

        if (method_exists($user, 'getPassword') && $user->getPassword()) {
            if (!$passwordHasher->isPasswordValid($user, $password)) {
                $this->addFlash('error', 'Invalid password for this guest account.');
                return $this->redirectToRoute('app_guest_portal');
            }
        }

        $session->set('guest_user_email', $email);
        $session->set('guest_authenticated', true);

        $this->addFlash('success', 'Welcome back, ' . $email . '! Job applications are unlocked.');
        return $this->redirectToRoute('app_job_index');
    }

    #[Route('/guest/logout', name: 'app_guest_logout', methods: ['POST', 'GET'])]
    public function guestLogout(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('guest_user_email');
        $session->remove('guest_authenticated');

        $this->addFlash('info', 'You have logged out of the Guest Portal.');
        return $this->redirectToRoute('app_guest_portal');
    }

    #[Route('/jobs/{id}/apply', name: 'app_job_apply', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function applyForJob(int $id, Request $request, JobRepository $jobRepository): Response
    {
        $session = $request->getSession();
        $isGuest = (bool) $session->get('guest_authenticated', false);
        $guestEmail = (string) $session->get('guest_user_email', '');

        if (!$isGuest || $guestEmail === '') {
            $this->addFlash('error', 'Please create a guest account or log in on the Guest Page before applying for a job.');
            return $this->redirectToRoute('app_guest_portal');
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
            $this->addFlash('success', 'Application submitted by ' . $guestEmail . ' for "' . $jobTitle . '"!');
        } else {
            $this->addFlash('info', 'You have already applied for "' . $jobTitle . '" with ' . $guestEmail . '.');
        }

        return $this->redirectToRoute('app_job_show', ['id' => $id]);
    }

    #[Route('/admin', name: 'app_admin_portal', methods: ['GET', 'POST'])]
    public function adminPortal(Request $request, UserRepository $userRepository): Response
    {
        $session = $request->getSession();

        if ($request->isMethod('POST')) {
            $username = trim((string) $request->request->get('username', ''));
            $password = (string) $request->request->get('password', '');

            if ($username === 'admin' && $password === 'admin') {
                $session->set('admin_authenticated', true);
                $session->set('admin_username', 'admin');
                $this->addFlash('success', 'Admin access granted. Welcome to Candidate Listing.');
                return $this->redirectToRoute('app_admin_candidates');
            }

            $this->addFlash('error', 'Invalid admin credentials. Use username "admin" and password "admin".');
            return $this->redirectToRoute('app_admin_portal');
        }

        $isAdmin = (bool) $session->get('admin_authenticated', false);
        $candidates = $isAdmin ? $userRepository->findAll() : [];

        return $this->render('portal/admin.html.twig', [
            'isAdmin' => $isAdmin,
            'candidates' => $candidates,
        ]);
    }

    #[Route('/admin/candidates', name: 'app_admin_candidates', methods: ['GET'])]
    public function adminCandidates(Request $request, UserRepository $userRepository): Response
    {
        if (!$request->getSession()->get('admin_authenticated', false)) {
            $this->addFlash('error', 'Admin login required (user: admin / pass: admin) to view Candidate Listing.');
            return $this->redirectToRoute('app_admin_portal');
        }

        return $this->render('candidate/index.html.twig', [
            'candidates' => $userRepository->findAll(),
        ]);
    }

    #[Route('/admin/logout', name: 'app_admin_logout', methods: ['POST', 'GET'])]
    public function adminLogout(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('admin_authenticated');
        $session->remove('admin_username');

        $this->addFlash('info', 'Admin session closed.');
        return $this->redirectToRoute('app_admin_portal');
    }
}