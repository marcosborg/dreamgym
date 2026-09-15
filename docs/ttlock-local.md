# TTLock no Dream Gym — diagnóstico local

## Estado

Existe um fluxo de PINs de reserva com providers `simulated`, `manual_ihr` e um `ihr_api` por implementar. Esta preparação acrescenta apenas autenticação e descoberta TTLock. Não muda o provider, as reservas, os intervalos de acesso ou a produção. Não abre portas nem cria/revoga PINs ou eKeys.

Hardware indicado pelo utilizador: cilindro HR Smart L153, nome na app `Dream Gym 1`, gateway G5 `Netlook`. A abertura remota pela app foi confirmada pelo utilizador. A identificação pela API e a compatibilidade com PINs/eKeys ainda não foram verificadas. Uma abertura remota bem-sucedida não comprova suporte a PINs temporários.

## Configuração privada e primeiro teste

1. Na TTLock Open Platform, criar uma aplicação e obter aprovação, Client ID e Client Secret. Confirmar com o portal a região/endpoint correspondente à conta da app.
2. Editar o `.env` local, já excluído do Git, e acrescentar:

   ```dotenv
   TTLOCK_BASE_URL=https://euapi.ttlock.com
   TTLOCK_CLIENT_ID="preencher localmente"
   TTLOCK_CLIENT_SECRET="preencher localmente"
   ```

   O endpoint acima é uma opção para a região europeia: confirmar antes de usar. O diagnóstico também aceita os endpoints oficiais `https://api.ttlock.com` e `https://api.sciener.com`. Não escolher a região apenas pela localização física da fechadura.
3. No terminal local, na pasta do projeto, executar:

   ```sh
   /Applications/MAMP/bin/php/php8.4.17/bin/php artisan config:clear
   /Applications/MAMP/bin/php/php8.4.17/bin/php artisan ttlock:discover
   ```

4. Introduzir a conta e a palavra-passe **da app TTLock que contém Dream Gym 1**, nos dois campos ocultos. Não colocar palavras-passe em argumentos de comandos nem no chat. O comando calcula o MD5 exigido pela API em memória. A conta de developer é distinta da conta usada para autenticar as fechaduras.
5. Confirmar que surge `Dream Gym 1`, registar o `lockId` e verificar `Gateway associado`. Esta indicação não comprova que o gateway está online neste momento. A descoberta lista todas as páginas e todas as fechaduras; não escolhe automaticamente a primeira nem depende de nomes únicos.

Tokens não são persistidos nem apresentados. Cada execução autentica novamente. Erros apresentados são sanitizados; dados sensíveis da fechadura não integram o resultado. Não ativar ferramentas de captura de pedidos HTTP durante autenticação.

## Próxima fase

Depois de identificar a fechadura, confirmar capacidades reais do L153 e o método de acesso desejado. Definir associação explícita entre salas e um ou mais `lockId`, antes de ligar o provisionamento às reservas. Será necessário implementar armazenamento cifrado/renovação de tokens, tratamento de falhas, idempotência, revogação e testes de validade/cancelamento. Nenhuma associação ou operação física foi criada nesta fase.

## Verificação

Testes com respostas HTTP simuladas cobrem autenticação, paginação, filtragem de dados, configuração ausente, recusa de endpoints externos e erros TTLock. Não houve autenticação real porque faltam as credenciais. Não se alteraram dados da base de dados.

## Fontes oficiais

- Credenciais e aprovação da aplicação: https://n8ndoc.ttlock.com/guide/authentication.html
- Autenticação e formato da palavra-passe: https://euopen.ttlock.com/doc/oauth2
- Lista de fechaduras e campos: https://euopen.ttlock.com/doc/api/v3/lock/list
- Formato das chamadas: https://euopen.ttlock.com/doc/api
