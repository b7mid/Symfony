<?php

namespace App\Controller;

use App\Repository\JobRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class JobController extends AbstractController
{
    #[Route('/jobs', name: 'app_job_index')]
    public function index(JobRepository $jobRepository): Response
    {
        $jobs = $jobRepository->findAll();

        return $this->render('job/index.html.twig', [
            'jobs' => $jobs,
        ]);
    }

    #[Route('/jobs/{id}', name: 'app_job_show', requirements: ['id' => '\d+'])]
    public function show(int $id, JobRepository $jobRepository): Response
    {
        $job = $jobRepository->find($id);

        if (!$job) {
            throw $this->createNotFoundException('The requested job listing does not exist.');
        }

        return $this->render('job/show.html.twig', [
            'job' => $job,
        ]);
    }
}