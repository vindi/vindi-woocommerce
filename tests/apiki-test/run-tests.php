<?php
/**
 * Apiki Test Runner — Validates JSON fixtures for Vindi WooCommerce scenarios.
 *
 * Como rodar:
 *   php tests/apiki-test/run-tests.php
 *
 * O que esse script faz:
 *   1. Carrega cada pedido-*.json
 *   2. Extrai o "cenário" esperado (ID do cenário, descrição, ids no WooCommerce/Vindi)
 *   3. Extrai o "como o sistema deve estar":
 *      - Produto(s) WC que devem existir
 *      - Produto(s)/Plano(s) Vindi correspondentes
 *      - Configurações do plugin (trial days, SUF, shipping)
 *   4. Valida a estrutura interna (campos obrigatórios, IDs reais do log)
 *   5. Exibe, por cenário, o que o plugin DEVE enviar pra Vindi (expected_payload)
 *
 * Custo: zero chamadas externas. Roda offline.
 *
 * @package VindiPaymentGateway
 */

declare(strict_types=1);

// -----------------------------------------------------------------------------
// Configuração: cores ANSI e helpers de UI
// -----------------------------------------------------------------------------

if (!function_exists('ansi')) {
    function ansi(string $code, string $text): string
    {
        if (getenv('NO_COLOR') !== false) {
            return $text;
        }
        return "\033[{$code}m{$text}\033[0m";
    }
}

if (!function_exists('green')) {
    function green(string $t): string  { return ansi('32', $t); }
    function red(string $t): string    { return ansi('31', $t); }
    function yellow(string $t): string { return ansi('33', $t); }
    function blue(string $t): string   { return ansi('34', $t); }
    function bold(string $t): string   { return ansi('1', $t); }
    function dim(string $t): string    { return ansi('2', $t); }
}

if (!function_exists('hr')) {
    function hr(string $char = '=', int $len = 80): string
    {
        return str_repeat($char, $len) . "\n";
    }
}

// -----------------------------------------------------------------------------
// Validação: IDs reais vindo do log da sandbox
// -----------------------------------------------------------------------------

/**
 * IDs reais da sandbox (extraídos do log de 2026-08-23).
 * Mantém a fonte da verdade do que existe na Vindi.
 */
$KNOWN_VINDI_IDS = [
    'products' => [
        225724 => '[WC] Taxa de adesão (SUF) — usado em pedidos antigos',
        232097 => 'Frete (Taxa fixa) — criado no pedido 5789',
        519552 => 'Assinatura Simples (5784) — sem trial, sem SUF',
        519553 => 'Assinatura Simples Preço promocional (5785)',
        519554 => 'Assinatura Simples 30 dias gratis (5786)',
        519555 => 'Assinatura Simples 30 dias gratis + SUF (5787)',
    ],
    'plans' => [
        106997 => 'Plan da Assinatura Simples (5784)',
        106998 => 'Plan da Preço promocional (5785)',
        106999 => 'Plan do Trial 30d (5786)',
        107000 => 'Plan do Trial 30d + SUF (5787)',
    ],
    'shipping' => [232097],
    'suf'      => [225724],
    'customer' => [1869180],
];

/**
 * IDs reais dos produtos WC configurados no WooCommerce da Apiki.
 */
$KNOWN_WC_PRODUCT_IDS = [
    5784 => 'Assinatura Simples — mensal, sem trial, sem SUF',
    5785 => 'Assinatura Simples Preço promocional',
    5786 => 'Assinatura Simples 30 dias gratis',
    5787 => 'Assinatura Simples 30 dias gratis + taxa de inscrição',
];

// -----------------------------------------------------------------------------
// Carregamento dos fixtures
// -----------------------------------------------------------------------------

$fixturesDir = __DIR__;
$files = glob($fixturesDir . '/pedido-*.json') ?: [];

if (empty($files)) {
    echo red("Nenhum fixture encontrado em {$fixturesDir}\n");
    exit(1);
}

sort($files);

echo bold(green("Apiki Test Runner — Vindi WooCommerce\n"));
echo dim("Cenários carregados: " . count($files) . "\n");
echo hr();

// -----------------------------------------------------------------------------
// Cabeçalho do sumário
// -----------------------------------------------------------------------------

$pass = 0;
$fail = 0;
$warnings = 0;
$scenarios = [];

foreach ($files as $file) {
    $basename = basename($file);
    $raw = file_get_contents($file);
    $data = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo red("  ✗ {$basename} — JSON inválido: " . json_last_error_msg() . "\n");
        $fail++;
        continue;
    }

    $errors = [];
    $warns = [];

    // Campos obrigatórios
    foreach (['_comment', '_cenario', 'order', 'vindi_ids', 'expected_payload'] as $key) {
        if (!array_key_exists($key, $data)) {
            $errors[] = "campo obrigatório ausente: {$key}";
        }
    }

    if ($errors) {
        foreach ($errors as $err) {
            echo red("  ✗ {$basename} — {$err}\n");
        }
        $fail++;
        continue;
    }

    // ---- Validação dos IDs no bloco vindi_ids ----
    $vi = $data['vindi_ids'];

    if (!empty($vi['product_id']) && !isset($KNOWN_VINDI_IDS['products'][$vi['product_id']])) {
        $errors[] = sprintf(
            'vindi.product_id=%d não existe na sandbox. Use um dos: %s',
            $vi['product_id'],
            implode(', ', array_keys($KNOWN_VINDI_IDS['products']))
        );
    }

    if (!empty($vi['plan_id']) && !isset($KNOWN_VINDI_IDS['plans'][$vi['plan_id']])) {
        $errors[] = sprintf(
            'vindi.plan_id=%d não existe na sandbox. Use um dos: %s',
            $vi['plan_id'],
            implode(', ', array_keys($KNOWN_VINDI_IDS['plans']))
        );
    }

    if (!empty($vi['shipping_product_id']) && !in_array($vi['shipping_product_id'], $KNOWN_VINDI_IDS['shipping'], true)) {
        $errors[] = 'vindi.shipping_product_id=' . $vi['shipping_product_id'] . ' não está na lista de IDs de frete conhecidos (' . implode(',', $KNOWN_VINDI_IDS['shipping']) . ')';
    }

    if (!empty($vi['suf_product_id']) && !in_array($vi['suf_product_id'], $KNOWN_VINDI_IDS['suf'], true)) {
        $errors[] = 'vindi.suf_product_id=' . $vi['suf_product_id'] . ' não está na lista de IDs de SUF conhecidos (' . implode(',', $KNOWN_VINDI_IDS['suf']) . ')';
    }

    if (!empty($vi['customer_id']) && !in_array($vi['customer_id'], $KNOWN_VINDI_IDS['customer'], true)) {
        $errors[] = 'vindi.customer_id=' . $vi['customer_id'] . ' não é o customer de sandbox conhecido';
    }

    // ---- Validação do product_id WC no order ----
    foreach (($data['order']['items'] ?? []) as $idx => $item) {
        $wcId = $item['product_id'] ?? null;
        if ($wcId !== null && isset($KNOWN_WC_PRODUCT_IDS[$wcId]) === false && $wcId !== 9999) {
            // Aviso, não erro: produto avulso (9999) é placeholder
            $warns[] = "order.items[{$idx}].product_id={$wcId} não está nos produtos WC conhecidos (5784-5787)";
        }
    }

    // ---- Validação cruzada: has_trial vs product_id Vindi ----
    $firstItem = $data['order']['items'][0] ?? [];
    $hasTrialWc = (bool) ($firstItem['has_trial'] ?? false);

    $productVindiHasTrial = false;
    if (!empty($vi['product_id'])) {
        $productVindiHasTrial = in_array($vi['product_id'], [519554, 519555], true);
    }
    if ($hasTrialWc && !$productVindiHasTrial && empty($vi['plan_id'])) {
        $warns[] = "order diz has_trial=true mas product Vindi (" . ($vi['product_id'] ?? 'n/d') . ") não suporta trial";
    }

    // ---- Validação: shipping_total vs presence of _subscription ----
    $hasSubShipping = !empty($data['order']['_subscription']['shipping_method']);
    $orderShipping  = (float) ($data['order']['shipping_total'] ?? 0);

    if ($hasSubShipping && $orderShipping > 0) {
        $warns[] = 'order tem frete E _subscription tem frete — isso é ambíguo';
    }
    if (!$hasSubShipping && $orderShipping > 0 && empty($vi['shipping_product_id'])) {
        $warns[] = 'order tem shipping_total > 0 mas nenhum shipping_product_id mapeado';
    }
    if ($hasSubShipping && empty($vi['shipping_product_id'])) {
        $warns[] = '_subscription tem frete mas não há shipping_product_id no vindi_ids';
    }

    // Acumula resultado
    $scenarios[] = [
        'file'    => $basename,
        'data'    => $data,
        'errors'  => $errors,
        'warns'   => $warns,
    ];

    $fail += count($errors);
    $warnings += count($warns);
    if (empty($errors)) {
        $pass++;
    }
}

echo bold("Sumário da validação\n");
echo "  Cenários OK:  " . green((string) $pass) . "\n";
echo "  Com avisos:   " . yellow((string) $warnings) . "\n";
echo "  Com erros:    " . red((string) $fail) . "\n";
echo hr();

// -----------------------------------------------------------------------------
// Renderização detalhada de cada cenário
// -----------------------------------------------------------------------------

foreach ($scenarios as $s) {
    $data = $s['data'];

    echo bold(blue("📦 " . ($data['_cenario'] ?? $s['file']) . "\n"));
    echo dim("    arquivo: " . $s['file'] . "\n\n");

    // 1. Descrição do pedido (o que é)
    echo yellow("  ▸ O que é este pedido\n");
    echo "    " . ($data['_comment'] ?? '(sem descrição)') . "\n\n";

    // 2. Como o sistema precisa estar (precondições WC + Vindi)
    echo yellow("  ▸ Como o sistema precisa estar configurado\n");

    $vi = $data['vindi_ids'];
    $order = $data['order'];

    echo bold("    WordPress / WooCommerce:\n");
    foreach ($order['items'] ?? [] as $idx => $item) {
        $prodId = $item['product_id'] ?? '?';
        $knownDesc = $KNOWN_WC_PRODUCT_IDS[$prodId] ?? 'produto avulso de teste';
        echo "      • item[{$idx}]: WC product_id={$prodId}";
        if (isset($KNOWN_WC_PRODUCT_IDS[$prodId])) {
            echo dim("  ({$knownDesc})");
        }
        echo "\n";
        echo "        nome='" . ($item['name'] ?? '') . "'"
            . " price=" . ($item['price'] ?? '?')
            . " qty=" . ($item['quantity'] ?? '?')
            . " is_subscription=" . ($item['is_subscription'] ? 'true' : 'false')
            . "\n";
        if ($item['has_trial'] ?? false) {
            echo "        trial_days=" . ($item['trial_days'] ?? 0) . " dias\n";
        }
        if ($item['has_suf'] ?? false) {
            echo "        SUF (taxa de inscrição) = R$ " . number_format((float) ($item['suf_price'] ?? 0), 2, ',', '.') . "\n";
        }
    }
    echo "      • shipping_method='" . ($order['shipping_method'] ?? '') . "'"
        . " shipping_total=R$ " . number_format((float) ($order['shipping_total'] ?? 0), 2, ',', '.') . "\n";

    if (!empty($order['_subscription'])) {
        echo "      • _subscription presente:\n";
        echo "          shipping_method='" . ($order['_subscription']['shipping_method'] ?? '') . "'\n";
        echo "          total_shipping=R$ " . number_format((float) ($order['_subscription']['total_shipping'] ?? 0), 2, ',', '.') . "\n";
    }

    echo "\n" . bold("    Vindi (sandbox):\n");
    echo "      • customer_id  = {$vi['customer_id']}  (Apiki Teste)\n";
    if ($vi['product_id']) {
        echo "      • product_id   = {$vi['product_id']}  " . dim("(" . ($KNOWN_VINDI_IDS['products'][$vi['product_id']] ?? '?') . ")") . "\n";
    } else {
        echo "      • product_id   = " . dim("(nenhum — compra avulsa)") . "\n";
    }
    if ($vi['plan_id']) {
        echo "      • plan_id      = {$vi['plan_id']}  " . dim("(" . ($KNOWN_VINDI_IDS['plans'][$vi['plan_id']] ?? '?') . ")") . "\n";
    } else {
        echo "      • plan_id      = " . dim("(nenhum — sem assinatura)") . "\n";
    }
    if ($vi['shipping_product_id']) {
        echo "      • shipping_id  = {$vi['shipping_product_id']}  " . dim("(Frete Taxa fixa)") . "\n";
    }
    if ($vi['suf_product_id']) {
        echo "      • suf_id       = {$vi['suf_product_id']}  " . dim("(Taxa de adesão)") . "\n";
    }

    // 3. Plugins settings que precisam estar ativas
    echo "\n" . bold("    Plugin Vindi — settings necessárias:\n");
    $needsTrial = $order['items'][0]['has_trial'] ?? false;
    $needsSuf = $order['items'][0]['has_suf'] ?? false;
    $needsShip = !empty($order['shipping_method']) || !empty($order['_subscription']['shipping_method']);
    echo "      • Vindi WooCommerce: " . ($needsTrial || $needsSuf || $needsShip ? yellow("configurações manuais necessárias") : green("configuração padrão")) . "\n";
    if ($needsTrial) {
        echo "        - \"Trial\" configurado no produto WC (" . ($order['items'][0]['trial_days'] ?? 0) . " dias)\n";
    }
    if ($needsSuf) {
        echo "        - \"Taxa de adesão\" configurada no produto WC (R$ " . number_format((float) ($order['items'][0]['suf_price'] ?? 0), 2, ',', '.') . ")\n";
    }
    if ($needsShip && $order['_subscription']['shipping_method'] ?? false) {
        echo "        - Frete que migra pra subscription (WC_Shipping) — exige o fix do build_shipping_item\n";
    }

    // 4. O que o plugin DEVE enviar pra Vindi
    echo "\n" . bold(yellow("  ▸ O que o plugin DEVE enviar pra Vindi (expected_payload)\n"));

    $payload = $data['expected_payload'];
    if (isset($payload['type']) && $payload['type'] === 'bill') {
        echo "    → tipo: bill avulsa (sem assinatura)\n";
    } else {
        echo "    → tipo: subscription (cria assinatura + primeira fatura automática)\n";
    }

    echo "    {\n";
    if (isset($payload['customer_id']))           echo "      \"customer_id\": {$payload['customer_id']},\n";
    if (isset($payload['payment_method_code']))  echo "      \"payment_method_code\": \"{$payload['payment_method_code']}\",\n";
    if (isset($payload['installments']))         echo "      \"installments\": {$payload['installments']},\n";
    if (isset($payload['plan_id']))              echo "      \"plan_id\": \"{$payload['plan_id']}\",\n";
    if (isset($payload['code']))                 echo "      \"code\": \"{$payload['code']}\",\n";

    $items = $payload['product_items'] ?? [];
    if (!empty($items)) {
        echo "      \"product_items\": [\n";
        foreach ($items as $i => $pi) {
            $trailing = ($i < count($items) - 1) ? ',' : '';
            $lineCount = 0;
            echo "        {\n";
            $lines = [];
            if (array_key_exists('product_id', $pi)) {
                $lines[] = "          \"product_id\": " . (is_int($pi['product_id']) ? $pi['product_id'] : 'null');
            }
            if (array_key_exists('quantity', $pi)) {
                $lines[] = "          \"quantity\": {$pi['quantity']}";
            }
            if (array_key_exists('cycles', $pi)) {
                $lines[] = "          \"cycles\": " . ($pi['cycles'] === null ? 'null' : $pi['cycles']);
            }
            if (isset($pi['pricing_schema'])) {
                $lines[] = "          \"pricing_schema\": " . json_encode($pi['pricing_schema'], JSON_UNESCAPED_SLASHES);
            }
            if (isset($pi['discounts'])) {
                $lines[] = "          \"discounts\": " . json_encode($pi['discounts'], JSON_UNESCAPED_SLASHES);
            }
            // Remove trailing comma from last line of each item
            $last = array_pop($lines);
            foreach ($lines as $ln) {
                echo $ln . ",\n";
            }
            // Last field gets the trailing comma only if not the absolute last item
            $sep = ($i < count($items) - 1) ? ',' : '';
            echo $last . $sep . "\n";
            echo "        }\n";
        }
        echo "      ]\n";
    }
    echo "    }\n";

    // 5. Avisos e erros deste cenário
    if (!empty($s['errors'])) {
        echo "\n" . red("  ✗ ERROS:\n");
        foreach ($s['errors'] as $err) {
            echo "      - {$err}\n";
        }
    }
    if (!empty($s['warns'])) {
        echo "\n" . yellow("  ⚠ AVISOS:\n");
        foreach ($s['warns'] as $w) {
            echo "      - {$w}\n";
        }
    }

    echo hr();
}

// -----------------------------------------------------------------------------
// Rodapé com próximos passos
// -----------------------------------------------------------------------------

echo bold(green("\n✔ Resumo\n"));
echo "  OK: {$pass} | Avisos: {$warnings} | Erros: {$fail}\n\n";

if ($fail > 0) {
    echo red("  Há erros de configuração nos fixtures — corrija antes de implementar os testes.\n\n");
    exit(1);
}

echo dim("Próximos passos sugeridos:\n");
echo "  1. Implementar test runner que carrega WC_Order/WC_Subscription dos fixtures\n";
echo "  2. Stub RoutesApi pra capturar o payload gerado\n";
echo "  3. Comparar com expected_payload usando assertEquals\n";
echo "  4. Rodar: phpunit tests/apiki-test/  (após implementar mocks)\n\n";
