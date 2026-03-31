# Fase 3: Hacer dominios configurables

## Objetivo
Reemplazar los 100+ dominios hardcodeados `hackerexperience.com` con variables de `config.php`.

## Variables disponibles (definidas en Fase 1)
- `$gameDomain` — dominio principal
- `$gameDomainProto` — protocolo + dominio (http://...)
- `$forumDomain` — forum.dominio
- `$wikiPath` — URL completa al wiki
- `$contactEmail` — email de contacto

## Tareas

### Templates (mayor concentración de dominios)
- [ ] `template/default.php` (~20 referencias) — og:image, og:url, language switcher, footer links, CDN refs
- [ ] `template/fbtpl.php` — Facebook URLs, domain detection
- [ ] `template/tttpl.php` — Twitter URLs
- [ ] `template/contentEnd.php` — footer links

### Clases
- [ ] `classes/Session.class.php` (líneas 557-618) — detección de idioma por hostname (`$_SERVER['HTTP_HOST']` comparado contra subdominios), links al wiki
- [ ] `classes/Premium.class.php` — email from address, postback URLs
- [ ] `classes/Mail.class.php` — email body con URLs hardcodeadas
- [ ] `classes/Player.class.php` — email de contacto
- [ ] `classes/Clan.class.php` — link al forum
- [ ] `classes/List.class.php` — link al wiki
- [ ] `classes/SES.class.php` (stub) — URL de bienvenida
- [ ] `classes/Forum.class.php` (stub) — URL del forum

### Entry points
- [ ] `index.php` — Facebook redirect URLs (br./en. subdominios)
- [ ] `settings.php` — header redirects con dominio
- [ ] `privacy.php` — referencias a piwik.hackerexperience.com y dominio principal
- [ ] `TOS.php` — referencias legales al dominio
- [ ] `ajax.php` — email de contacto

### Otros
- [ ] `certs/1-3.php` — dominio en certificados
- [ ] `status/index.php` — página de status
- [ ] `ses/listener.php` — (si no fue borrado en Fase 0) beta.hackerexperience.com

### Nota sobre `$_SERVER['HTTP_HOST']`
En `classes/Session.class.php` y `index.php` hay lógica que compara `$_SERVER['HTTP_HOST']` contra subdominios específicos (br., en.) para determinar idioma. Refactorizar para usar:
```php
$subdomainMap = [
    $gameDomain => 'en_US.UTF-8',
    'br.' . $gameDomain => 'pt_BR.UTF-8',
    'en.' . $gameDomain => 'en_US.UTF-8',
];
```

## Verificación
```bash
# No deben quedar hackerexperience.com (excepto README, LICENSE, comentarios con "2019:")
grep -rn 'hackerexperience\.com' --include='*.php' | grep -v README | grep -v LICENSE | grep -v '2019:'
```
