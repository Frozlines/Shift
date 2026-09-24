# SHIFT Store

Loja de serviços de marketing construída com **PHP puro + MySQL/MariaDB + HTML + JavaScript puro + Tailwind CSS**.

Mantém a estrutura visual da versão frontend anterior: homepage, catálogo, página de produto/serviço, carrinho e checkout; acrescenta autenticação, área de cliente, favoritos, moradas, métodos de pagamento tokenizados, encomendas e painel de administração.

## Requisitos

- XAMPP recente (Apache + MySQL/MariaDB + PHP 8.1+)
- Extensões PHP: `pdo_mysql`, `mbstring`
- Internet para carregar Tailwind via CDN

## Instalação no XAMPP

1. Extrai a pasta com o nome **`shift-store`** para:

   `C:\xampp\htdocs\shift-store`

2. No XAMPP Control Panel, inicia:
   - Apache
   - MySQL

3. Abre:

   `http://localhost/shift-store/setup.php`

4. Escolhe o email e password do primeiro administrador.

5. Depois abre:

   `http://localhost/shift-store/`

O instalador cria automaticamente a base de dados `shift_store`, as tabelas, as configurações e os serviços de demonstração. Depois da primeira instalação fica bloqueado por `storage/installed.lock`.

## Se mudares o nome da pasta

Edita `config/app.php`:

```php
'base_url' => '/shift-store',
```

Por exemplo, se a pasta for `shift`:

```php
'base_url' => '/shift',
```

## Roles

- `CUSTOMER`: conta normal de cliente.
- `ADMIN`: administração da loja.

O utilizador não autenticado é tratado como **guest**, não como uma role guardada na BD.

## Cliente

A área `/account` inclui:

- dados de conta;
- encomendas;
- favoritos;
- moradas;
- métodos de pagamento tokenizados;
- logout.

O carrinho funciona também sem login através da sessão PHP. Quando o utilizador inicia sessão, o carrinho guest é fundido com o carrinho persistente da conta.

## Carrinho e checkout

- Carrinho persistente na base de dados para clientes autenticados.
- Carrinho por sessão para guests.
- Quantidades revalidadas no servidor.
- Preços e stock revalidados no checkout.
- Snapshot de nome/preço/quantidade em `order_items`.
- Stock é reduzido dentro de uma transação SQL.
- Portes padrão: `4,90 €`.
- Portes grátis a partir de `75,00 €`.
- Ambos são editáveis pelo administrador em **Configurações**.

## Pagamentos e cartões

A aplicação **não guarda número completo do cartão nem CVV**.

A tabela `payment_methods` guarda apenas o tipo de informação que um gateway como Stripe/Mollie/Adyen devolveria depois de tokenizar o cartão:

- `provider`
- `provider_token`
- `brand`
- `last_four`
- `expiry_month`
- `expiry_year`

Nesta versão o token é demonstrativo (`demo_pm_...`) e **não existe cobrança real**. O checkout cria uma entrada em `payments` com estado `PENDING`.

Para produção, substitui a tokenização demo por Stripe/Mollie/Adyen e atualiza pagamentos através de webhooks assinados.

## Administração

`/admin` inclui:

- Dashboard
- Produtos/serviços (criar/editar/ativar/desativar)
- Categorias
- Encomendas e atualização de estados
- Clientes
- Configurações da loja

Estados de encomenda:

- `PENDING`
- `PROCESSING`
- `SHIPPED`
- `DELIVERED`
- `CANCELLED`
- `REFUNDED`

Estados de pagamento:

- `PENDING`
- `PAID`
- `FAILED`
- `REFUNDED`


## Frontend interativo

A interface continua em **JavaScript puro + Tailwind**, sem frameworks, mas inclui agora:

- toasts empilháveis para sucesso, informação e erros;
- modais próprios de confirmação em vez de `confirm()` do browser;
- estados de loading e confirmação visual nos botões;
- mini-carrinho lateral no cabeçalho;
- pesquisa instantânea com sugestões de serviços;
- skeleton loading nos catálogos e resumos;
- transições e reveal suave de secções/cards;
- feedback visual em favoritos e contador do carrinho;
- confirmação antes de remover itens, moradas, métodos de pagamento e categorias;
- confirmação final antes de criar uma encomenda;
- filtros da loja atualizados sem reload e sincronizados com o URL;
- botão de voltar ao topo;
- mensagens de servidor que podem ser fechadas sem recarregar a página.

## Segurança já incluída

- `password_hash()` / `password_verify()`
- sessões com cookies `HttpOnly` e `SameSite=Lax`
- regeneração do ID de sessão no login/logout
- CSRF em ações mutáveis
- prepared statements PDO
- validação server-side
- controlo de acesso ADMIN/CUSTOMER
- revalidação de preço e stock no servidor
- transações no checkout
- snapshot de dados históricos da encomenda
- diretórios internos protegidos por `.htaccess`

## Antes de produção

Ainda deves acrescentar/testar:

- HTTPS obrigatório e cookie `Secure` em produção;
- gateway de pagamento real + webhooks;
- emails transacionais;
- recuperação/verificação de email;
- rate limiting de login e APIs;
- política de privacidade/cookies/termos;
- faturação/IVA conforme a operação real;
- backups;
- logs estruturados e monitorização;
- testes automatizados;
- secrets fora do web root / variáveis de ambiente;
- CSP e restantes headers de segurança.

## Estrutura principal

```text
shift-store/
├── index.php
├── shop.php
├── product.php
├── cart.php
├── checkout.php
├── setup.php
├── api/
├── auth/
├── account/
├── admin/
├── assets/
├── config/
├── database/
├── src/
└── storage/
```


## Upload de imagens

No painel Admin > Produtos, a imagem pode ser escolhida diretamente do computador.

- Formatos aceites: JPG, PNG e WebP
- Tamanho máximo: 5 MB
- Ficheiros guardados em `uploads/products/`
- O nome físico é aleatório para evitar colisões
- Ao substituir uma imagem já enviada, o upload anterior é removido
- SVG não é aceite como upload por motivos de segurança

Em XAMPP, confirma que PHP pode escrever na pasta `uploads/products/` (normalmente funciona por defeito no Windows).


## Categorias dinâmicas

As categorias são administradas em `admin/categories.php`. O administrador pode criar, editar, ativar/desativar e remover categorias vazias.

O cabeçalho, footer e filtros da loja leem diretamente as categorias ativas da base de dados, por isso uma nova categoria aparece automaticamente sem alterações ao código.

Por segurança de integridade, uma categoria que ainda tenha serviços associados não pode ser removida. Primeiro move esses serviços para outra categoria; em alternativa, a categoria pode ser ocultada/desativada.

## Gestão de utilizadores e estados de conta

O Admin > Utilizadores permite editar nome, email, telefone e role (`CUSTOMER`/`ADMIN`), além de bloquear, reativar ou eliminar logicamente contas.

Estados especiais:

- `BLOCKED`: a conta fica inativa e não consegue iniciar sessão; pode ser reativada pelo admin.
- `DELETED`: soft delete. O registo e histórico de encomendas são preservados, a conta deixa de iniciar sessão e o nome passa a `DELETED-XXXXXXX`.

A aplicação guarda internamente a role anterior de uma conta bloqueada para a poder restaurar quando for reativada. O administrador atual não pode bloquear/eliminar a própria conta, e a aplicação impede que o último administrador ativo seja removido ou despromovido.

A atualização da estrutura da tabela `users` é aplicada automaticamente uma única vez através de `schema_migrations`, portanto instalações existentes não precisam de apagar a base de dados.

## Confirmação de logout

O link **Terminar sessão** usa agora o modal de confirmação da própria SHIFT. A página de detalhe de uma encomenda também inclui um botão **Voltar** que regressa à página anterior e usa a lista de encomendas como fallback.
