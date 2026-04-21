# Diagnóstico do Problema de Login

## Problema Relatado
"request missing data payload" - Login não funciona

## Investigação Realizada

### 1. **Fluxo de Requisição (Cliente → Servidor)**

**Frontend:** `frontend/auth/login.html` + `assets/js/api-config.js`
- Formulário coleta `email` e `password`
- Função `login(email, password)` em `api-config.js:229` envia dados:
  ```javascript
  const formData = new URLSearchParams();
  formData.append('action', 'login');
  formData.append('email', email);
  formData.append('password', password);
  
  fetch(getApiUrl('auth/login.php'), {
      method: 'POST',
      body: formData.toString(),  // ← Converte para string URL-encoded
      headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
      }
  })
  ```

**Backend:** `api/auth/login.php`
- Valida `$_POST['action']`, `$_POST['email']`, `$_POST['password']`
- Retorna JSON com sucesso/erro

### 2. **Possíveis Causas do Erro**

#### Cenário A: Validação HTML não preenchida
- Se `email` ou `password` estão vazios no formulário, o HTML impede envio (atributo `required`)
- Nesse caso, o erro não viria do servidor, mas do navegador

#### Cenário B: Dados não chegam ao servidor
- Se o `URLSearchParams.toString()` não está gerando string válida
- **Solução:** Usar `new FormData()` em vez de `URLSearchParams` (linha 239 de `api-config.js`)

#### Cenário C: Validação PHP não clara
- O PHP valida campos obrigatórios nas linhas 471-475 de `login.php`
- Se faltarem dados, retorna: `"Email e senha sao obrigatorios"`
- Erro é retornado com status HTTP 400

### 3. **Recomendações de Teste**

#### Teste 1: Validar form-data corretamente
```javascript
// Em api-config.js:239, mudar de:
body: formData.toString(),

// Para:
body: formData,  // FormData é automaticamente serializado corretamente
```

#### Teste 2: Adicionar logging de debug
```javascript
// Em api-config.js:235-243
apiDebugLog('Login Request:', {
    email: email,
    password: password ? '***' : 'VAZIO',
    method: 'POST',
    endpoint: getApiUrl('auth/login.php')
});
```

#### Teste 3: Verificar resposta do servidor
```bash
# Testar via curl
curl -X POST http://localhost/anateje/api/auth/login.php \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "action=login&email=teste@example.com&password=senha123"
```

### 4. **Validação no Backend (Funcionando)**

O arquivo `api/auth/login.php` está **funcionando corretamente**:
- ✅ Valida campos obrigatórios
- ✅ Retorna JSON com status apropriado
- ✅ Rate limiting implementado
- ✅ Segurança de sessão implementada

### 5. **Próximos Passos**

1. **Verificar no browser:**
   - Abrir DevTools (F12)
   - Ir para aba "Network"
   - Clicar em "Login"
   - Ver a requisição POST para `/api/auth/login.php`
   - Verificar se tem "Form Data" ou se está vazio

2. **Testar função login() diretamente:**
   ```javascript
   // No console do navegador
   login('test@example.com', 'password123').then(r => console.log(r))
   ```

3. **Rodar testes automatizados:**
   ```bash
   cd /wamp64/www/oldanateje
   php ./vendor/bin/phpunit tests/Unit/AuthLoginMethodTest.php
   ```

## Conclusão

A causa mais provável é **FormData não estar sendo serializada corretamente** quando convertida para string. O código em `api-config.js:239` deveria passar o `FormData` diretamente, não seu `.toString()`.

**Mudança recomendada em `api-config.js:239`:**
```javascript
// ANTES (pode causar problema)
body: formData.toString(),

// DEPOIS (correto)
body: formData,
```

Isso garantirá que o navegador serializa o FormData corretamente com os headers apropriados.
