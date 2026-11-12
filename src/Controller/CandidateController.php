<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CandidateController extends AbstractController
{
    #[Route('/candidates', name: 'app_candidate_index')]
    public function index(UserRepository $userRepository): Response
    {
        // Fetch all candidates/users from the repository
        $candidates = $userRepository->findAll();

        return $this->render('candidate/index.html.twig', [
            'candidates' => $candidates,
        ]);
    }

    #[Route('/candidates/{id}', name: 'app_candidate_show')]
    public function show(int $id, UserRepository $userRepository): Response
    {
        // Safely fetch a single candidate by ID for the summary page
        $candidate = $userRepository->find($id);

        if (!$candidate) {
            throw $this->createNotFoundException('The requested candidate does not exist.');
        }

        return $this->render('candidate/show.html.twig', [
            'candidate' => $candidate,
        ]);
    }
}