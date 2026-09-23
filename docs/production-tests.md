# Testes de produção — Dream Gym

O site está online; combinar os testes reais com o titular e registar as reservas utilizadas. Usar uma conta de teste autorizada sem créditos nem mensalidade ativa para testar pagamentos; uma reserva feita com créditos não testa a ifthenpay.

1. Registar o número da reserva, horário e valor mostrado. Iniciar um MB WAY e confirmar na aplicação do titular. Não marcar como pago manualmente.
2. Confirmar no backoffice o pagamento ifthenpay recebido por callback, uma única reserva confirmada, um único PIN provisionado e um email com número da reserva, horário e PIN. Verificar inbox e spam.
3. Repetir com referência Multibanco: entidade, referência e valor devem corresponder; o titular faz o pagamento e verifica-se a confirmação automática.
4. Com David no local e dentro do horário autorizado, testar o PIN na fechadura. O estado “provisionado” não prova abertura física.
5. Criar uma reserva pendente sem iniciar pagamento, esperar 15 minutos e confirmar que a vaga volta a estar disponível. Uma reserva com pedido já iniciado mantém-se até ao prazo do pedido ou início da reserva, o que ocorrer primeiro.
6. Confirmar que uma conta diferente e um visitante não conseguem consultar a reserva ou o PIN. A reserva de visitante só pode ser consultada na sessão em que foi criada.
7. Cancelar uma reserva de créditos com pelo menos 12 horas de antecedência: devolver um crédito uma única vez e revogar o PIN. Com menos de 12 horas, cancelar sem devolver crédito. O cancelamento não faz reembolsos bancários automáticos.
8. Verificar a coluna “Ação necessária” em Pagamentos. Pagamentos tardios sem vaga ou de reservas canceladas requerem reagendamento/reembolso manual, sem ativar acesso.

Diagnóstico no servidor, sem expor credenciais:

```sh
php artisan production:readiness
php artisan production:readiness --send-test-email
```

A segunda opção envia um email técnico para o endereço remetente configurado. “accepted_by_transport” significa aceitação pelo transporte, não entrega na caixa de entrada. Confirmar receção separadamente. O comando não cria reservas/pagamentos nem abre portas.

Publicação via cPanel: atualizar o repositório e executar Deploy HEAD Commit. A `.cpanel.yml` aplica a migração e recompila as caches. O cron `ttlock:sync` deve continuar a correr a cada minuto. Não eliminar a nova coluna ao fazer rollback: é compatível com a versão anterior, mas essa versão reintroduz os problemas corrigidos.

Reconciliação automática de MB WAY (a cada minuto, além do callback):

```sh
cd /home4/dreamgym/public_html && /usr/local/bin/php artisan payments:sync >> storage/logs/payments-sync.log 2>&1
```

Consulta apenas pedidos existentes na API autenticada da ifthenpay; não inicia cobranças. Confirma reservas e compras de forma idempotente, incluindo tentativas anteriores. Processa pendentes dos últimos sete dias; para um pagamento mais antigo, usar `payments:sync --payment=ID`. Multibanco continua a depender do callback autenticado. Pagamentos tardios sem vaga continuam a exigir revisão. O cron `ttlock:sync` também deve executar a cada minuto.
