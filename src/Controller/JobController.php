<?php

namespace App\Controller;

use App\Entity\Job;
use App\Entity\Candidature;
use App\Form\JobType;
use App\Form\CandidatureType;
use App\Repository\JobRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class JobController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(EntityManagerInterface $em): Response
    {
        $repo = $em->getRepository(Candidature::class);
        $lesCandidats = $repo->findAll();

        return $this->render('job/home.html.twig', [
            'lesCandidats' => $lesCandidats
        ]);
    }

    #[Route(['/job', '/jobs'], name: 'app_job_index', methods: ['GET'])]
    public function index(Request $request, JobRepository $jobRepository): Response
    {
        $search = $request->query->get('q');
        $jobs = $jobRepository->findBySearchQuery($search);

        return $this->render('job/index.html.twig', [
            'jobs' => $jobs,
            'search' => $search,
        ]);
    }

    #[Route('/job/new', name: 'app_job_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $job = new Job();
        $form = $this->createForm(JobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($job);
            $entityManager->flush();

            $this->addFlash('success', 'Job posting created successfully!');
            return $this->redirectToRoute('app_job_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('job/new.html.twig', ['job' => $job, 'form' => $form]);
    }

    #[Route('/job/{id}', name: 'app_job_show', methods: ['GET'])]
    public function show(Job $job, EntityManagerInterface $entityManager): Response
    {
        $listCandidatures = $entityManager->getRepository(Candidature::class)->findBy(['job' => $job]);

        return $this->render('job/show.html.twig', [
            'job' => $job,
            'listCandidatures' => $listCandidatures,
        ]);
    }

    #[Route('/job/{id}/edit', name: 'app_job_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Request $request, Job $job, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(JobType::class, $job);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Job posting updated successfully!');
            return $this->redirectToRoute('app_job_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('job/edit.html.twig', ['job' => $job, 'form' => $form]);
    }

    #[Route('/job/{id}/delete', name: 'app_job_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, Job $job, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$job->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($job);
            $entityManager->flush();
            $this->addFlash('success', 'Job posting deleted.');
        }

        return $this->redirectToRoute('app_job_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/candidature/ajouter', name: 'add_candidat', methods: ['GET', 'POST'])]
    public function ajouter_cand(Request $request, EntityManagerInterface $em): Response
    {
        $candidat = new Candidature();
        $form = $this->createForm(CandidatureType::class, $candidat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $cvFile */
            $cvFile = $form->get('cvFile')->getData();

            if ($cvFile) {
                $newFilename = uniqid().'.'.$cvFile->guessExtension();
                try {
                    $cvFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/cvs',
                        $newFilename
                    );
                    $candidat->setCvFilename($newFilename);
                } catch (\Exception $e) {
                    // handle exception
                }
            }

            $em->persist($candidat);
            $em->flush();

            $this->addFlash('success', 'Candidat ajouté avec succès !');
            return $this->redirectToRoute('home');
        }

        return $this->render('job/ajouter.html.twig', [
            'f' => $form->createView()
        ]);
    }

    #[Route('/candidature/supp/{id}', name: 'cand_delete', methods: ['GET', 'POST'])]
    public function deleteCand(int $id, EntityManagerInterface $entityManager): Response
    {
        $c = $entityManager->getRepository(Candidature::class)->find($id);
        if (!$c) {
            throw $this->createNotFoundException('Aucune candidature trouvée pour l\'id ' . $id);
        }

        $entityManager->remove($c);
        $entityManager->flush();

        $this->addFlash('success', 'Candidature supprimée avec succès.');
        return $this->redirectToRoute('home');
    }

    #[Route('/candidature/editU/{id}', name: 'edit_user', methods: ['GET', 'POST'])]
    public function editCand(Request $request, int $id, EntityManagerInterface $em): Response
    {
        $candidat = $em->getRepository(Candidature::class)->find($id);
        if (!$candidat) {
            throw $this->createNotFoundException('Aucune candidature trouvée pour l\'id ' . $id);
        }

        $form = $this->createForm(CandidatureType::class, $candidat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $cvFile */
            $cvFile = $form->get('cvFile')->getData();

            if ($cvFile) {
                $newFilename = uniqid().'.'.$cvFile->guessExtension();
                try {
                    $cvFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/cvs',
                        $newFilename
                    );
                    $candidat->setCvFilename($newFilename);
                } catch (\Exception $e) {
                    // handle exception
                }
            }

            $em->flush();
            $this->addFlash('success', 'Candidature mise à jour avec succès.');
            return $this->redirectToRoute('home');
        }

        return $this->render('job/ajouter.html.twig', [
            'f' => $form->createView()
        ]);
    }
}