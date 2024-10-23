=== Vindi WooCommerce 2 ===
Contributors: apiki, aguiart0, laertejr
Plugin Name: Vindi WooCommerce
Plugin URI: https://github.com/vindi/vindi-woocommerce
Website Link: https://www.vindi.com.br
Tags: vindi, cobrança-recorrente, vindi-woocommerce, assinaturas, woocommerce-subscriptions
Author URI: https://vindi.com.br/ | https://mentores.com.br
Author: Vindi | Mentores Digital
Requires at least: 4.4
Tested up to: 6.4
WC requires at least: 3.0.0
WC tested up to: 8.6.1
Requires PHP: 5.6
Stable Tag: 1.3.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Venda de assinaturas de produtos e serviços pelo plugin de cobrança recorrente para o WooCommerce.

== Description ==
O **Vindi WooCommerce** oferece uma solução completa para pagamentos únicos e assinaturas com cartão de crédito, boleto, bolePix e Pix utilizando o [Woocommerce Subscriptions](https://www.woothemes.com/products/woocommerce-subscriptions/). Basta ter [uma conta habilitada na Vindi](https://www.vindi.com.br/cadastro/) para começar a cobrar seus clientes.

A [Vindi](https://www.vindi.com.br/) é líder em cobrança recorrente no Brasil. Com milhares de clientes usando soluções como pagamento online, soluções de notas fiscais integradas, emissão de boletos por email e PDF, integrações com ERPs e diversos relatórios, a Vindi possibilita um sistema online completo para negócios de venda recorrente. Além disso, empresas podem usar o gateway de pagamento integrado ao billing recorrente ou para faturas avulsas.

== Installation ==

Para verificar os requisitos e efetuar a instalação do plugin, [siga as instruções na documentação oficial](https://atendimento.vindi.com.br/hc/pt-br/articles/227335608).

== Frequently Asked Questions ==

Para dúvidas e suporte técnico, entre em contato com a equipe Vindi através da nossa [central de atendimento](https://atendimento.vindi.com.br/hc/pt-br).

== Screenshots ==

1. Nova campo de inserção de informações do cartão de crédito
2. Página de cofiguração do plugin
3. Página de pagamento com os boletos para impressão
4. Opção de reembolso automático do pedido
5. Configurações de pagamentos via cartão de crédito

== Changelog ==
= 1.3.4 - 31/07/2024 =
-Lançamento da versão de patch.
- **Adição:** Implementação de botão para gerar link de pagamento utilizando métodos da Vindi.
- **Adição:** Inclusão de botão para copiar o link de pagamento com métodos da Vindi.
- **Adição:** Remoção do botão pagar no my order para itens de renovação.
- **Adição:** Novos campos de meta value adicionados para os assinatura de renovação de renovação
- **Melhoria:** Refatoração e otimização do código.

= 1.3.3 - 05/07/2024 =
-Lançamento da versão de patch.
- **Correção:** Estava sendo enviado o ID do produto variável em vez do ID do produto.

= 1.3.2 - 22/05/2024 =
-Lançamento da versão de patch.
- **Correção:** As assinaturas com trial não estão mais passando do tempo limite
- **Correção:** Texto da descrição do plugin agora possui bolePix e Pix
- **Correção:** Alteração do texto que informava a proibição de ter assinatura e produto avulso no mesmo carrinho

= 1.3.1 - 15/05/2024 =
-Lançamento da versão de patch.
- **Correção:** Utilização de cupons para desconto dos produtos que possuem taxa
- **Correção:** Não é mais possível a comprar de uma assinatura com produto avulso no mesmo carrinho
- **Correção:** Não pode mais comprar duas assinaturas iguais

= 1.3.0 - 06/05/2024 =
-Lançamento da versão de patch.
- **Melhoria:** Refatoração nas funções de processamento de webhooks
- **Correção:** Atualização de expressões regulares para identificação de bandeiras de cartão


= 1.2.9 - 18/04/2024 =
-Lançamento da versão de patch.
- **Correção:** Utilização da função save durante atualização de metadados com HPOS
- **Correção:** Bug durante renovação de assinaturas

= 1.2.8 - 05/04/2024 =
-Lançamento da versão de patch.
- **Melhoria:** Adiciona o método de pagamento BolePix
- **Correção:** Bug ao atualizar produtos do tipo assinatura
- **Melhoria:** Os webhook tiveram melhorias em seus retornos

= 1.2.7 - 04/04/2024 =
-Lançamento da versão de patch.
- **Melhoria:** Adiciona o método de pagamento Pix

= 1.2.6 - 28/02/2024 =
-Lançamento da versão de patch.
- **Melhoria:** Validação do plugins para as novas versões WooCommerce, WordPress e PHP

= 1.2.5 - 12/10/2023 =
-Lançamento da versão de patch.
- **Correção:** Validação das depenências do plugin
- **Correção:** Funcionamento da opção de sincronismo de assinaturas

= 1.2.4 - 30/08/2023 =
-Lançamento da versão de patch.
- **Correção:** Removido campo de seleção de parcelas para produtos sem parcelamento
- **Correção:** Utilização de cupons de desconto por porcentagem em conjunto com taxa de adesão
- **Correção:** Divergência na cobrança de juros de cartão de crédito

= 1.2.3 - 30/06/2023 =
-Lançamento da versão de patch.
- **Correção:** Aparição dos métodos de pagamentos em pedidos zerados somente para pedidos de teste gratis.
- **Correção:** visualização das parcelas crédito de acordo com o valor total do pedido
- **Correção:** Erro fatal ao cancelar inscrições em ambientes com PHP 8+

= 1.2.2 - 25/04/2023 =
-Lançamento da versão de patch.
- **Correção:** Valor de parcelas de crédito para produtos variáveis.

= 1.2.1 - 19/04/2023 =
-Lançamento da versão de patch.
- **Correção:** Erro fatal ao receber notificação de Webhook

= 1.2.0 - 29/03/2023 =
-Lançamento da versão de patch.
- **Correção:** Correção de scripts que faziam o update das informações do checkout durante a seleção de uma parcela. Algumas informações ocultas eram perdidas no processo.
- **Correção:** Duplicação de produtos variáveis.
- **Adição:** Adiciona a funcionalidade de controlar as configurações de parcelamento das assinaturas.
- **Correção** Corrige a renderização dos métodos de pagamento para assinaturas com free trial ativado.
- **Correção** Corrige o valor das parcelas, que se mantinham zerados caso o valor do primeiro ciclo fosse zerado.
- **Correção** Corrige a utilização de cupons em assinaturas que possuem o free trial ativado.
- **Correção** Vinculo com a VINDI eram sobrescritos durante edição de produtos.
- **Correção** Compatibilidade com as versões mais recentes do PHP.

= 1.1.13 - 10/08/2022 =
-Lançamento da versão de patch.
- **Correção**: Duplicação de produtos dentro do WooCommerce
- **Adição**: Adicionado suporte à funcionalidade de "one time shipping" do WooCommerce
  Subscriptions, que permite opção de cobrança única (somente no primeiro ciclo de uma assinatura)
- **Correção**: Correção de um comportamento de alerta de notificação que induzia o usuário a
  desmarcar a opção "Turn off Automatic Payments" de forma incorreta.


= 1.1.12 - 08/06/2022 =
-Lançamento da versão de patch.
- **Correção**: Foi removido o envio da descrição do produto para a plataforma Vindi para evitar possíveis erros no cadastro de produtos.


= 1.1.11 - 07/12/2021 =
-Lançamento da versão de patch.
- **Correção**: Foi corrigida a versão da dependência WooCommerce Subscriptions, necessária para funcionamento do plugin.
- **Correção**: Foi corrigido um comportamento que ocasionava a exibição indevida de informações sobre juros na tela de checkout.


= 1.1.10 - 13/10/2021 =
-Lançamento da versão path.
- **Correção**: Foi corrigido um comportamento em que assinaturas do plugin antigo do WooCommerce não conseguiam ser canceladas pelo plugin novo.


= 1.1.9 - 04/10/2021 =
- Lançamento da versão de patch.
- **Correção**: Foi corrigido o comportamento que permitia inserir qualquer tipo de dado no campo referente a numeração do cartão no checkout.


= 1.1.8 - 23/09/2021 =
- Lançamento da versão de patch.
- **Correção**: Foi corrigido o comportamento que exibia erros JS em páginas diferentes do checkout ou quando não possuiam a opção de método de pagamento Cartão de crédito.


= 1.1.7 - 05/05/2021 =
- Lançamento da versão de minor.
- **Adição**: Foi adicionado o tipo de cupom de desconto Recurring Product % Discount (nativo do Woocommerce Subscriptions) integrado com a API da Vindi.


= 1.1.6 - 16/04/2021 =
- Lançamento da versão de patch.
- **Correção**: Foi corrigido o comportamento que impedia que compras avulsas com frete pudessem ser realizadas.


= 1.1.5 - 08/04/2021 =
- Lançamento da versão de patch.
- **Correção**: Foi corrigido o comportamento que impossibilitava a visualização de boletos na página minha conta.
- **Adição**: Foi adicionado o suporte a novas bandeiras de cartão de crédito.
Lista de bandeiras aceitas:
- Hipercard
- Elo
- American Express
- Visa
- Mastercard
- Discover
- Diners Club
- JCB

**Importante**: Caso alguma dessas bandeiras não esteja habilitada na plataforma Vindi e haja uma tentativa de compra, não será possível criar o perfil de pagamento, impossibilitando a conclusão da compra.


= 1.1.4 - 05/04/2021 =
- Lançamento da versão de patch.
- **Correção**: Foi corrigido o comportamento que impossibilitava a visualização de boletos no checkout na adesão de uma ou mais assinaturas.


= 1.1.3 - 19/03/2021 =
- Lançamento da versão de patch.
- **Correção**: Foi corrigido o comportamento de atualização de status das assinaturas caso o plugin WooCommerce Memberships esteja ativo.


= 1.1.2 - 15/03/2021 =
- Lançamento da versão de patch.
- **Correção**: Foi corrigido o comportamento na compra de assinaturas variantes do mesmo produto (ex.: tamanho P e G), cujos planos/periodicidades são diferentes (ex.: mensal e anual). Essas assinaturas serão criadas separadamente na Plataforma Vindi.


= 1.1.1 - 01/02/2021 =
- Lançamento da versão de patch.
- **Adição**: Foi inserida a opção para selecionar a quantidade de parcelas nas assinaturas conforme o cadastro do plano;
- **Correção**: Corrigida a mensagem de rejeição de pagamento no checkout;
- **Correção**: Corrigido o comportamento quando o pagamento é rejeitado em planos com a cobrança é no término do período;
- **Correção**: Corrigida a verificação de dependências necessárias para o correto funcionamento do plugin;
- **Correção**: Corrigida a informação sobre a data do próximo pagamento em caso de renovação de assinaturas;


= 1.1.0 - 08/01/2021 =
- Lançamento da versão de patch.
- **Correção**: Corrigido o comportamento da taxa de adesão para assinaturas, que agora é cobrado apenas no primeiro ciclo da assinatura;
- **Adição**: Foi inserida uma mensagem de configuração de renovação automática das assinaturas;
Essa configuração é necessária para garantir o correto funcionamento do plugin.
- **Correção**: Foi corrigido o comportamento de cancelamento indevido de assinaturas após a reativação via painel administrativo;
- **Adição**: Foi inserido o suporte para múltiplas assinaturas no mesmo checkout;
Múltiplas assinaturas exibirão múltiplos boletos no checkout;
Os valores referentes a fretes serão enviados especificamente por assinaturas;


= 1.0.4 - 18/12/2020 =
- Lançamento da versão de patch.
- **Correção**: Corrigido o problema de reembolso via painel administrativo.


= 1.0.3 - 08/12/2020 =
- Lançamento da versão de patch.
- **Correção**: Corrigida a falha no ciclo de renovação de assinaturas.
- **Correção**: Corrigida a falha em assinaturas de planos com período trial.
- **Adição**: Compatibilidade nas renovações de assinatura do plugin anterior.


= 1.0.2 - 25/11/2020 =
- Lançamento da versão de patch.
- **Correção**: Corrigido a falha crítica na adesão de assinaturas por boleto e cartão.
- **Correção**: Corrigido periodicidade do frete para assinaturas recorrentes.


= 1.0.1 - 28/10/2020 =
- Lançamento da versão de patch.
- **Correção**: Removido obrigatoriedade de login do usuário para compras avulsas.
- **Correção**: Corrigido a instabilidade na conexão com a API da Vindi nas configurações do Woocommerce.

= 1.0.0 - 22/06/2020 =
- Lançamento da versão inicial.
- **Adição**: Template para tradução do plugin.
- **Adição**: Vizualização dos boletos dentro do pedido.
- **Melhoria**: Novo campo de cartão de crédito.
- **Melhoria**: Checkout de assinaturas e produtos simples em uma única compra.
- **Melhoria**: Centralização da configuração da chave api.
- **Melhoria**: Criação dos produtos via Woocommerce sem precisar acessar o painel da Vindi.
- **Melhoria**: Configuração de desconto por cupom.
- **Melhoria**: Estorno automático pelo painel do woocommerce do valor total de compras por cartão de crédito.
- **Melhoria**: Separação de assinaturas no painel.
- **Melhoria**: Juros configuráveis em compras parceladas.

== Upgrade Notice ==
= 1.1.11 - 07/12/2021 =
Patch de correções para o plugin Vindi

== License ==

Vindi WooCommerce is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

Vindi WooCommerce is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with Vindi WooCommerce. If not, see http://www.gnu.org/licenses/.
