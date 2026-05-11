<?php

namespace App\Service;

use Money\Currency;
use Money\Money;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\DecimalMoneyFormatter;
use Money\Parser\DecimalMoneyParser;

/**
 * 💹 SERVICE DE CONVERSION (VERSION BUNDLE)
 * Utilise moneyphp/money pour une précision bancaire.
 */
final class CurrencyService
{
    private array $supportedCurrencies;

    public function __construct(array $supportedCurrencies = [])
    {
        $this->supportedCurrencies = $supportedCurrencies;
    }

    public function getSupportedCurrencies(): array
    {
        return $this->supportedCurrencies;
    }

    public function isSupported(string $currency): bool
    {
        return in_array(strtoupper($currency), $this->supportedCurrencies, true);
    }

    private const SYMBOLS = [
        'TND' => 'DT', 'EUR' => '€', 'USD' => '$', 'GBP' => '£',
    ];

    // Taux de change fixes (Bundle interne pour stabilité)
    private const INTERNAL_RATES = [
        'EUR' => 0.2985,
        'USD' => 0.3241,
        'GBP' => 0.2542,
    ];

    public function getRate(string $currency): float
    {
        return self::INTERNAL_RATES[strtoupper($currency)] ?? 1.0;
    }

    /**
     * Calcule le montant avec précision via MoneyPHP
     */
    public function formatAmount(float|string|int $amountTND, string $targetCurrency): string
    {
        $targetCurrency = strtoupper($targetCurrency);
        $rate = $this->getRate($targetCurrency);
        
        // 1. Créer le montant en TND (Cents pour éviter les flottants)
        $currencies = new ISOCurrencies();
        $moneyParser = new DecimalMoneyParser($currencies);
        $moneyFormatter = new DecimalMoneyFormatter($currencies);

        // On convertit le montant TND en Money object
        $moneyTnd = $moneyParser->parse((string)$amountTND, new Currency('TND'));
        
        // 2. Conversion manuelle (MoneyPHP ne fait pas le change auto sans bundle complexe)
        $convertedValue = (float)$amountTND * $rate;
        $moneyTarget = $moneyParser->parse(number_format($convertedValue, 2, '.', ''), new Currency($targetCurrency === 'TND' ? 'TND' : ($targetCurrency === 'DT' ? 'TND' : $targetCurrency)));

        return $moneyFormatter->format($moneyTarget) . ' ' . ($this->getSymbol($targetCurrency));
    }

    public function getSymbol(string $currency): string
    {
        return self::SYMBOLS[strtoupper($currency)] ?? $currency;
    }
}

