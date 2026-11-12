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
        // Generate an array of 10 random numbers between 1 and 500
        $numbers = [];
        for ($i = 0; $i < 10; $i++) {
            $numbers[] = random_int(1, 500);
        }

        $min = min($numbers);
        $max = max($numbers);
        $average = array_sum($numbers) / count($numbers);

        return $this->render('numbers/index.html.twig', [
            'numbers' => $numbers,
            'min' => $min,
            'max' => $max,
            'average' => round($average, 2),
        ]);
    }
}