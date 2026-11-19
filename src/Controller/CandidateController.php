<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/candidate')]
class CandidateController extends AbstractController
{
    #[Route('', name: 'app_candidate_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        if (!$request->getSession()->get('admin_authenticated', false)) {
            $this->addFlash('error', 'Admin authentication required to view candidates.');
            return $this->redirectToRoute('app_unified_access');
        }

        return $this->render('candidate/index.html.twig', [
            'candidates' => $userRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_candidate_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, Request $request, UserRepository $userRepository): Response
    {
        if (!$request->getSession()->get('admin_authenticated', false)) {
            $this->addFlash('error', 'Admin authentication required to inspect candidate profiles.');
            return $this->redirectToRoute('app_unified_access');
        }

        $candidate = $userRepository->find($id);
        if (!$candidate instanceof User) {
            throw $this->createNotFoundException('Candidate not found.');
        }

        return $this->render('candidate/show.html.twig', [
            'candidate' => $candidate,
        ]);
    }
}