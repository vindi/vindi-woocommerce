Contexto do problema

É realizada uma venda do produto "Teste APi", configurado com:

Valor recorrente: R$ 10,00/mês
Trial: 7 dias gratuitos
Taxa de inscrição (SUF): R$ 8,00
Frete: R$ 30,00

O checkout é realizado pelo WooCommerce, utilizando o WooCommerce Subscriptions para gerenciamento da assinatura. Após a finalização da compra, o Plugin Vindi converte o pedido do WooCommerce em uma assinatura na Vindi.

Comportamento esperado

A Vindi deve receber os valores de forma separada:

Produto: R$ 10,00/mês — cobrança recorrente
SUF: R$ 8,00 — cobrança única
Frete: R$ 30,00 — cobrança única, mantendo o mesmo valor calculado pelo WooCommerce
Comportamento atual

Atualmente, o plugin está enviando corretamente:

✅ Produto: R$ 10,00/mês
✅ SUF: R$ 8,00
❌ Frete: R$ 30,00 não é enviado para a Vindi
Por que o frete não é enviado

Durante o processo de criação da assinatura, o WooCommerce cria o pedido e, posteriormente, a WC_Subscription.

Nesse fluxo, o frete e o método de envio são transferidos do pedido para a assinatura. Dessa forma, em determinados cenários envolvendo assinatura com trial, o frete não está mais disponível diretamente em $this->order.

O método build_shipping_item() do plugin atualmente tenta obter essas informações diretamente do pedido:

$this->order->get_shipping_method()

Como o método de envio não está disponível nesse objeto, o retorno fica vazio. O plugin possui então uma condição de saída antecipada (early return) que encerra o processamento antes de tentar recuperar as informações da WC_Subscription.

Na prática, o fluxo atual é:

Pedido → não encontra o método de envio → early return → não consulta a WC_Subscription → frete não é enviado para a Vindi.

Impacto do trial e da SUF

Como a assinatura possui um trial de 7 dias, a Vindi não gera uma fatura imediatamente no momento da criação da assinatura. A cobrança fica configurada para ocorrer ao final do período de trial (billing_trigger_type: end_of_period).

Com isso, a SUF de R$ 8,00, apesar de ser enviada para a assinatura, não é cobrada imediatamente no momento da compra.

O cenário resultante é:

Cliente realiza a compra.
O cliente recebe 7 dias de trial.
A assinatura é criada na Vindi.
Ao final do trial, ocorre a cobrança da mensalidade.
A SUF fica vinculada à assinatura para cobrança posterior.
O frete, por sua vez, não é enviado pelo plugin devido ao problema descrito acima.
Causa identificada

O problema está no processo de construção do item de frete do plugin.

O plugin considera apenas o objeto $this->order como fonte para obtenção do frete. Porém, no fluxo com WooCommerce Subscriptions, o frete pode estar armazenado na WC_Subscription.

Como o método realiza o early return antes de consultar a assinatura, o valor do frete não chega à Vindi.

Resumo

Bug: o plugin não consegue recuperar o frete em pedidos com assinatura e trial.

Causa: o frete está disponível na WC_Subscription, mas o build_shipping_item() tenta obtê-lo exclusivamente através de $this->order.

Resultado: o frete de R$ 30,00 não é enviado para a Vindi, enquanto o produto de R$ 10,00/mês e a SUF de R$ 8,00 são processados corretamente.