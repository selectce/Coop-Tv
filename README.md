# 📺 SignageTV — Sistema de Digital Signage

## Como instalar via FTP

### Requisitos do servidor
- PHP 7.4+ (recomendado 8.0+)
- MySQL 5.7+ ou MariaDB 10.3+
- Módulo `mod_rewrite` habilitado (Apache)
- Extensões PHP: `pdo`, `pdo_mysql`, `fileinfo`, `gd`
- ffmpeg no servidor = geração automática de thumbnails e duração de vídeos
  (sem ffmpeg, ainda funciona — só não terá thumbnails e duração de vídeos)

---

### Passo a passo

**1. Upload dos arquivos**
- Suba toda a pasta `signage/` via FTP para o seu servidor
- Exemplo: `public_html/signage/` ou `public_html/`

**2. Acesse o instalador**
- Abra no navegador: `http://seusite.com.br/signage/install.php`
- Preencha os dados do banco de dados e crie seu usuário admin
- Clique em Instalar

**3. DELETE o install.php após instalar!**
- Por segurança, remova o `install.php` do servidor após a instalação

**4. Permissões**
- Certifique-se que a pasta `uploads/` tem permissão de escrita (755 ou 777)
- `chmod -R 755 uploads/` via SSH ou configure pelo painel da hospedagem

---

### Uso

**Painel Admin**
```
http://seusite.com.br/signage/login.php
```
Login padrão: `admin` / senha que você definiu no instalador

**Player para as TVs**
```
http://seusite.com.br/signage/player/index.php?store=loja-01
http://seusite.com.br/signage/player/index.php?store=loja-02
... etc
```
Cada loja tem seu próprio slug. Veja/edite em: Admin → Lojas

---

### Como configurar cada TV

1. Abra o navegador da TV (Chrome, Samsung Browser, LG Browser, etc.)
2. Acesse a URL do player daquela loja
3. Pressione o botão de tela cheia do navegador (ou tecle F11)
4. Para modo quiosque permanente, consulte o manual da sua TV/dispositivo:
   - **Android TV / Fire Stick**: instale um launcher quiosque
   - **Samsung Smart TV**: use o modo "Tela cheia" do navegador
   - **LG webOS**: use o modo "Quiosque" nas configurações do navegador
   - **Qualquer TV com Chrome**: F11 = tela cheia

---

### Controle Remoto (teclas suportadas)
| Tecla         | Ação             |
|---------------|------------------|
| Qualquer tecla | Mostra controles  |
| ←             | Vídeo anterior   |
| →             | Próximo vídeo    |
| OK / Enter    | Play / Pause     |
| ⏵ Play        | Continuar        |
| ⏸ Pause       | Pausar           |
| F5            | Recarregar       |
| Esc           | Esconder controles |
| Vermelho 🔴   | Vídeo anterior   |
| Verde 🟢      | Play/Pause       |
| Amarelo 🟡    | Próximo vídeo    |
| Azul 🔵       | Recarregar       |

---

### Estrutura de pastas
```
signage/
├── admin/          ← Painel administrativo
├── api/            ← APIs PHP
├── assets/         ← CSS e JS
├── db/             ← Schema SQL
├── includes/       ← Funções compartilhadas
├── player/         ← Player da TV
├── uploads/        ← Vídeos e imagens enviados
│   ├── videos/
│   ├── images/
│   └── thumbs/
├── config.php      ← Configurações (gerado pelo install.php)
├── install.php     ← ⚠ Delete após instalar!
└── .htaccess       ← Configurações Apache
```

---

### Funcionalidades
- ✅ 10 lojas com players independentes
- ✅ Upload de vídeos e imagens (múltiplos formatos, sem limite de tamanho)
- ✅ Editor de timeline com drag & drop
- ✅ Duração configurável por item
- ✅ Orientação vertical ou horizontal por loja
- ✅ Ordem sequencial ou aleatória
- ✅ Modo vídeo único (ignora timeline)
- ✅ Controle remoto universal (Samsung, LG, Android TV, Fire TV...)
- ✅ Relatórios completos com gráficos
- ✅ Atualização automática da playlist a cada 5 minutos
- ✅ Loop infinito
- ✅ Indicador de offline
- ✅ Barra de progresso
- ✅ Log de reproduções em tempo real
