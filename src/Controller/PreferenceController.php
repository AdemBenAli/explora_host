<?php

namespace App\Controller;

use App\Service\CurrencyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class PreferenceController extends AbstractController
{
    public function __construct(
        private readonly array $supportedLocales = ['fr', 'en', 'ar']
    ) {
    }

    #[Route('/preferences/locale/{locale}', name: 'app_set_locale', methods: ['GET'])]
    public function setLocale(Request $request, string $locale): RedirectResponse
    {
        $locale = strtolower(trim($locale));
        if (in_array($locale, $this->supportedLocales, true)) {
            $request->getSession()?->set('_locale', $locale);
        }

        $target = (string) $request->query->get('redirect', $request->headers->get('referer', '/'));
        return $this->redirect($target);
    }

    #[Route('/preferences/currency/{currency}', name: 'app_set_currency', methods: ['GET'])]
    public function setCurrency(Request $request, string $currency, CurrencyService $currencyService): RedirectResponse
    {
        $currency = strtoupper(trim($currency));
        if ($currencyService->isSupported($currency)) {
            $request->getSession()?->set('_currency', $currency);
        }

        $target = (string) $request->query->get('redirect', $request->headers->get('referer', '/'));
        return $this->redirect($target);
    }
}

