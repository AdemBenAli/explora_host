<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LocaleController extends AbstractController
{
    #[Route('/set-locale/{locale}', name: 'app_set_locale')]
    public function setLocale(string $locale, Request $request): Response
    {
        $request->getSession()->set('_locale', $locale);
        $request->setLocale($locale);

        $redirect = $request->query->get('redirect', $this->generateUrl('app_home'));
        return $this->redirect($redirect);
    }
}
