<?php

namespace App\Twig;

use App\Service\CurrencyService;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class AppExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly CurrencyService $currencyService,
        private readonly array $supportedLocales = ['fr', 'en', 'ar']
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('money', [$this, 'formatMoney']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('current_currency', [$this, 'getCurrentCurrency']),
            new TwigFunction('current_currency_symbol', [$this, 'getCurrentCurrencySymbol']),
            new TwigFunction('currency_rate', [$this, 'getCurrentRate']),
            new TwigFunction('supported_currencies', [$this, 'getSupportedCurrencies']),
            new TwigFunction('supported_locales', [$this, 'getSupportedLocales']),
        ];
    }

    public function formatMoney(float|string|int|null $amount): string
    {
        $value = $amount ?? 0;
        return $this->currencyService->formatAmount((float) $value, $this->getCurrentCurrency());
    }

    public function getCurrentCurrency(): string
    {
        $session = $this->requestStack->getSession();
        $currency = strtoupper((string) ($session?->get('_currency', 'TND') ?? 'TND'));

        return $this->currencyService->isSupported($currency) ? $currency : 'TND';
    }

    public function getCurrentCurrencySymbol(): string
    {
        return $this->currencyService->getSymbol($this->getCurrentCurrency());
    }

    public function getCurrentRate(): float
    {
        return $this->currencyService->getRate($this->getCurrentCurrency());
    }

    public function getSupportedCurrencies(): array
    {
        return $this->currencyService->getSupportedCurrencies();
    }

    public function getSupportedLocales(): array
    {
        return $this->supportedLocales;
    }
}

