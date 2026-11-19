<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NumbersController extends AbstractController
{
    #[Route('/numbers', name: 'app_numbers')]
    public function index(): Response
    {
        $csStats = [
            [
                'title' => 'Average Software Engineer Salary',
                'value' => '$125,000',
                'percent' => 83,
                'color' => '#3b82f6',
                'description' => 'Median annual compensation across full-stack, backend, and systems engineers.'
            ],
            [
                'title' => 'Projected Tech Job Growth (10-Yr)',
                'value' => '+25%',
                'percent' => 75,
                'color' => '#10b981',
                'description' => 'Significantly faster than the average growth rate across all other occupations.'
            ],
            [
                'title' => 'Remote & Hybrid Tech Roles',
                'value' => '58%',
                'percent' => 58,
                'color' => '#8b5cf6',
                'description' => 'Share of software and cybersecurity positions offering location flexibility.'
            ],
            [
                'title' => 'Python & AI Stack Demand',
                'value' => '48%',
                'percent' => 48,
                'color' => '#f59e0b',
                'description' => 'Proportion of engineering postings requesting Python, data mining, or ML tooling.'
            ],
            [
                'title' => 'Unfilled Cybersecurity Roles',
                'value' => '3.5M',
                'percent' => 92,
                'color' => '#ef4444',
                'description' => 'Global talent shortage across network security, penetration testing, and SOC analysts.'
            ],
            [
                'title' => 'Enterprise Cloud Adoption',
                'value' => '89%',
                'percent' => 89,
                'color' => '#06b6d4',
                'description' => 'Organizations running production workloads on AWS, Microsoft Azure, or GCP.'
            ]
        ];

        return $this->render('numbers/index.html.twig', [
            'stats' => $csStats,
        ]);
    }
}