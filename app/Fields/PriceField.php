<?php

namespace App\Fields;

class PriceField extends BaseField
{
    public const CURRENCIES = [
        'RUB' => ['symbol' => '₽', 'name' => 'Российский рубль'],
        'USD' => ['symbol' => '$', 'name' => 'Доллар США'],
        'EUR' => ['symbol' => '€', 'name' => 'Евро'],
        'GBP' => ['symbol' => '£', 'name' => 'Фунт стерлингов'],
        'KZT' => ['symbol' => '₸', 'name' => 'Казахстанский тенге'],
        'BYN' => ['symbol' => 'Br', 'name' => 'Белорусский рубль'],
        'UAH' => ['symbol' => '₴', 'name' => 'Украинская гривна'],
        'CNY' => ['symbol' => '¥', 'name' => 'Китайский юань'],
    ];

    public function getType(): string
    {
        return 'price';
    }

    public function getName(): string
    {
        return 'Цена с валютой';
    }

    public function getIcon(): string
    {
        return 'bi-cash-coin';
    }

    public function getGroup(): string
    {
        return 'data';
    }

    public function getDatabaseColumn(): string
    {
        return 'jsonb';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $data = $this->decode($value);
        $amount = $data['amount'] ?? '';
        $defaultCurrency = (string) ($config['config']['default_currency'] ?? 'RUB');

        if (!array_key_exists($defaultCurrency, self::CURRENCIES)) {
            $defaultCurrency = 'RUB';
        }

        $currency = $data['currency'] ?? $defaultCurrency;

        $name = e($config['name'] ?? '');
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= '<div class="input-group ztr-price-field">';
        $html .= "<input type=\"number\" id=\"{$id}\" name=\"{$name}[amount]\" class=\"form-control ztr-price-amount\""
               . ' value="' . e((string) $amount) . '" placeholder="0" step="0.01" min="0">';
        $html .= "<select name=\"{$name}[currency]\" class=\"form-select ztr-price-currency\">";

        foreach (self::CURRENCIES as $code => $info) {
            $sel = $currency === $code ? ' selected' : '';
            $html .= "<option value=\"{$code}\"{$sel}>{$code} ({$info['symbol']})</option>";
        }

        $html .= '</select>';
        $html .= '</div>';

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        if (!is_array($input)) {
            return null;
        }

        $amount = $input['amount'] ?? null;

        if ($amount === '' || $amount === null) {
            return null;
        }

        if (!is_numeric($amount)) {
            return null;
        }

        $currency = (string) ($input['currency'] ?? 'RUB');

        if (!array_key_exists($currency, self::CURRENCIES)) {
            $currency = 'RUB';
        }

        return json_encode([
            'amount'   => (float) $amount,
            'currency' => $currency,
        ]);
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $data = $this->decode($value);

        if (empty($data) || !isset($data['amount'])) {
            return '';
        }

        $amount = (float) $data['amount'];
        $currency = (string) ($data['currency'] ?? 'RUB');
        $info = self::CURRENCIES[$currency] ?? ['symbol' => '', 'name' => $currency];
        $symbol = $info['symbol'];
        $amountFmt = $this->formatAmount($amount);
        $formatted = $amountFmt . ($symbol !== '' ? ' ' . $symbol : '');

        if ($template !== null) {
            $template = str_replace('[value:amount]', e($amountFmt), $template);
            $template = str_replace('[value:currency]', e($currency), $template);
            $template = str_replace('[value:symbol]', e($symbol), $template);
            $template = str_replace('[value:formatted]', e($formatted), $template);

            return $template;
        }

        return e($formatted);
    }

    public function getTemplateInfo(): ?array
    {
        $default = <<<TPL
[field:{alias}]
  <span class="price">[value:amount] <span class="currency">[value:symbol]</span></span>
[/field:{alias}]
TPL;

        $hint = 'Парный тег с кастомным HTML.<br>'
              . 'Доступные токены:<br>'
              . '• <code>[value:amount]</code> - сумма с разделителями (1 234,56)<br>'
              . '• <code>[value:currency]</code> - код валюты (RUB, USD…)<br>'
              . '• <code>[value:symbol]</code> - символ валюты (₽, $, €)<br>'
              . '• <code>[value:formatted]</code> - готовая строка «1 234,56 ₽»';

        return ['default' => $default, 'hint' => $hint];
    }

    public function toArray(mixed $value): array
    {
        return $this->decode($value);
    }

    public function fromArray(array $data): mixed
    {
        return json_encode($data);
    }

    private function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function formatAmount(float $amount): string
    {
        if (floor($amount) == $amount) {
            return number_format($amount, 0, ',', "\u{00A0}");
        }

        return number_format($amount, 2, ',', "\u{00A0}");
    }
}
