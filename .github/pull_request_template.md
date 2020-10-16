## O que mudou
_Descreva em poucas palavras o que muda para clientes ou colaboradores da Vindi. Não use termos técnicos nesta descrição pois isso será divulgado para áreas de negócio da empresa. Caso a mudança seja puramente técnica ou interna sem impacto direto ao cliente, escreva "Mudanças internas"._

## Motivação
**Issue**: _Adicione o link para issue que originou este PR, caso exista._

_Descreva a motivação deste PR. Explique qual é o problema que queremos resolver._

## Solução proposta
_Descreva a solução proposta para resolver o problema mencionado na seção "Motivação", incluindo os detalhes técnicos necessários para entender essa solução._

## Como testar
_Descreva detalhadamente, passo a passo, como testar este PR. Especifique requisitos, como por exemplo, acesso prévio em alguma ferramenta._

## Riscos
_Inclua os riscos que este PR está introduzindo nos ambientes de produção. Isso será usado pela equipe de relacionamento, suporte técnico e SRE para identificar com mais agilidade a origem de problemas após o deploy. Um risco é algo que não é previsto, mas que existe chance de ocorrer simplesmente porque algum código relacionado foi alterado._

## Impactos negativos previstos
_Diferente do risco, o impacto negativo é algo que sabemos que irá acontecer e que com certeza irá interferir no uso do sistema, disparar alarmes de monitoramento ou aumentar a incidência de tickets de suporte. Exemplos: downtime durante modificação na infraestrutura; lentidão no banco de dados em mudanças que possuem comandos DDL; funcionalidades removidas temporariamente ou permanentemente; mudanças que gerem incompatiblidades na API ("breaking changes"). Não é necessário listar impactos positivos pois eles deverão estar listados na seção "Solução proposta"._

## Instruções para deploy
_Informe os requisitos, dependências e configurações adicionais que deverão ser executadas antes ou depois do deploy deste PR._

## Instruções para retorno
_Descreva detalhadamente o plano de retorno deste PR em caso de rollback, como reversão de migrations, remoção/alteração de variáveis de ambiente ou configurações._
