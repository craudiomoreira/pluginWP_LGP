# LGPD Consent Modal Plugin

Um plugin WordPress que exibe um modal de consentimento compatível com Elementor.

## Funcionalidades

- Modal popup com texto e link para regras
- Posições configuráveis (top, bottom, center)
- Botão fechar e botão aceitar
- Registra consentimentos com data/hora, IP, user agent
- Painel de administração para configuração de layout (fontes do Google, cores, tamanhos)
- Página de logs que mostra as aceitações

## Uso

1. Instale o plugin na pasta `wp-content/plugins/lgpd-consent`.
2. Ative-o no WordPress.
3. Vá em **Configurações > LGPD Consent** para ajustar o texto, cores, fonte etc. Além das configurações de estilo há um **interruptor para ativar/desativar** o modal; se estiver desativado ele não aparecerá.
4. O pop‑up é exibido automaticamente na home do site (página inicial) assim que um visitante chega, desde que ativado e o consentimento ainda não tenha sido registrado. Depois de aceito ele não reaparece graças ao cookie.

### Logs e exportação

A página **LGPD Logs** permite filtrar por intervalo de datas e exportar resultados em CSV via o botão "Export CSV".

### Integração com GTM/dataLayer

Quando o botão "Aceitar" é clicado, além de salvar consentimento e definir um cookie, um evento `lgpd_consent_given` é enviado para `window.dataLayer` se estiver disponível.

### Testes

Há um esqueleto de testes PHPUnit em `tests/` e um `phpunit.xml.dist` para facilitar a execução. Configure o ambiente de testes WordPress (por exemplo com `wp scaffold plugin-tests`) antes de rodar.

### Internacionalização

O arquivo POT inicial está em `languages/lgpd-consent.pot`. Atualize com `wp i18n make-pot . languages/lgpd-consent.pot`.

Contribuições são bem-vindas!
