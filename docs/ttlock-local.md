# TTLock no Dream Gym — integração e diagnóstico

## Estado

Existe um fluxo de PINs de reserva com providers `simulated`, `manual_ihr` e um `ihr_api` por implementar. O comando `ttlock:discover` mantém-se como diagnóstico sem escrita. O provider `ttlock` permite agora provisionar e revogar PINs temporários de reservas, conforme a secção de produção abaixo.

Hardware indicado pelo utilizador: cilindro HR Smart L153, nome na app `Dream Gym 1`, gateway G5 `Netlook`. A abertura remota pela app foi confirmada pelo utilizador. Em 18/09/2026 a API confirmou a Dream Gym 1 (lockId 34816850), PINs V4 e o gateway 1745068 online. Um PIN personalizado com dígitos 1–6 e validade futura de dois minutos foi criado, consultado e removido com sucesso. Uma abertura remota bem-sucedida não comprova suporte a PINs temporários.

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

## Verificação

Testes com respostas HTTP simuladas cobrem autenticação, paginação, filtragem de dados, configuração ausente, recusa de endpoints externos e erros TTLock. O diagnóstico inicial não usou credenciais reais. A validação de produção de 18/09/2026 está descrita acima.

## Fontes oficiais

- Credenciais e aprovação da aplicação: https://n8ndoc.ttlock.com/guide/authentication.html
- Autenticação e formato da palavra-passe: https://euopen.ttlock.com/doc/oauth2
- Lista de fechaduras e campos: https://euopen.ttlock.com/doc/api/v3/lock/list
- Formato das chamadas: https://euopen.ttlock.com/doc/api


## Integração de reservas em produção

O provider `ttlock` associa cada sala explicitamente através de `rooms.ttlock_lock_id`.
A conta autenticada é guardada no registo 1 de `ttlock_connections`, com credenciais e tokens cifrados pelo `APP_KEY` do servidor. Nunca alterar essa chave sem migrar os dados cifrados. A palavra-passe da conta não é guardada. O token é renovado automaticamente antes de expirar.

`LOCK_PROVIDER=ttlock` ativa o provisionamento após o commit da reserva paga. É usada a API de PIN personalizado V4 pelo gateway (`addType=2`), com os limites exatos de início/fim, incluindo os buffers configurados. O nome determinístico por reserva e a consulta prévia permitem recuperar respostas incertas sem duplicar PINs. O código só é apresentado como pronto depois da confirmação da TTLock.

Executar a cada minuto pelo cPanel:

```sh
cd /home4/dreamgym/public_html && /usr/local/bin/php artisan ttlock:sync >> storage/logs/ttlock-sync.log 2>&1
```

O comando renova tokens, tenta novamente PINs pendentes, envia o email quando o acesso fica pronto e repete revogações pendentes de reservas canceladas. A revogação imediata é tentada após o commit do cancelamento. Se o gateway estiver offline, a revogação continua pendente até recuperar; a validade original continua a aplicar-se na fechadura. Consultar `provision_status=revoke_pending` no backoffice. PINs manuais ou antigos sem associação TTLock não são importados nem removidos automaticamente.

Não são enviados comandos de abertura remota. Não se alteram PINs permanentes existentes. Reservas com acesso TTLock não podem ser reativadas, mudar de sala/horário nem ser apagadas enquanto o PIN não estiver revogado ou expirado. Para alterar, cancelar e criar uma nova reserva.

Antes da ativação: validar `lockId`, versão V4, gateway online, criação/consulta/revogação de um PIN de teste futuro e curto, e ausência de PINs antigos ativos a migrar. Validar também uma abertura física com o cliente. Para suspender novos provisionamentos, usar `LOCK_PROVIDER=manual_ihr`; isso não revoga PINs já criados na fechadura.
