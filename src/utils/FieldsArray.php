<?php

namespace VindiPaymentGateways;

class VindiFieldsArray
{
    public function fields_array()
    {
        return array(
            'enabled' => array(
                'title' => __('Habilitar/Desabilitar', VINDI),
                'label' => __('Habilitar pagamento via Cartão de Crédito com a Vindi', VINDI),
                'type' => 'checkbox',
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Título', VINDI),
                'type' => 'text',
                'description' => __('Título que o cliente verá durante o processo de pagamento.', VINDI),
                'default' => __('Cartão de Crédito', VINDI),
            ),
            'verify_method' => array(
                'title' => __('Transação de Verificação', VINDI),
                'type' => 'checkbox',
                'description' => __(' Realiza a transação de verificação em todos os novos pedidos. (Taxas adicionais por verificação poderão ser cobradas).', VINDI),
                'default' => 'no',
            ),
            'single_charge' => array('title' => __('Vendas Avulsas', VINDI), 'type' => 'title'),
            'smallest_installment' => array(
                'title' => __('Valor mínimo da parcela', VINDI),
                'type' => 'text',
                'description' => __('Valor mínimo da parcela, não deve ser inferior a R$ 5,00.', VINDI),
                'default' => '5',
            ),
            'installments' => array(
                'title' => __('Número máximo de parcelas', VINDI),
                'type' => 'select',
                'description' => __('Número máximo de parcelas para vendas avulsas. Deixe em 1x para desativar o parcelamento.', VINDI),
                'default' => '1',
                'options' => array(
                    '1' => '1x',
                    '2' => '2x',
                    '3' => '3x',
                    '4' => '4x',
                    '5' => '5x',
                    '6' => '6x',
                    '7' => '7x',
                    '8' => '8x',
                    '9' => '9x',
                    '10' => '10x',
                    '11' => '11x',
                    '12' => '12x'
                ),
            ),
            'enable_interest_rate' => array(
                'title' => __('Habilitar juros', VINDI),
                'type' => 'checkbox',
                'description' => __('Habilitar juros no parcelamento do pedido.', VINDI),
                'default' => 'no',
            ),
            'interest_rate' => array(
                'title' => __('Taxa de juros ao mês (%)', VINDI),
                'type' => 'text',
                'description' => __('Taxa de juros que será adicionada aos pagamentos parcelados.', VINDI),
                'default' => '0.1',
            )
        );
    }
}
