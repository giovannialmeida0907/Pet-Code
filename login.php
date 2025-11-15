<?php
// Inclui as variáveis de configuração e a função conectar_banco()
require_once('conexao.php');

// Inicia a sessão para armazenar mensagens de feedback
session_start();

// Define a conexão para o escopo global do script
global $table_name;
$conn = conectar_banco(); // Conecta ao DB no início

$message = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? '';

// Limpa as mensagens da sessão após exibir
unset($_SESSION['message']);
unset($_SESSION['message_type']);

// --- LÓGICA DE PROCESSAMENTO ---

// Processamento do Formulário de Registro
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    
    // 1. Obtém e sanitiza os dados (sem real_escape_string, pois estamos usando prepared statements)
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $pergunta = $_POST['pergunta'] ?? '';
    $resposta = $_POST['resposta'] ?? '';
    
    // Dados Opcionais
    $nome_pet = $_POST['nome_pet'] ?? '';
    $especie = $_POST['especie'] ?? '';
    $idade = (int)($_POST['idade'] ?? 0);

    // 2. Validação
    if ($senha !== $confirmar_senha) {
        $message = "Erro: As senhas não coincidem.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Erro: Formato de e-mail inválido.";
        $message_type = "error";
    } elseif (empty($nome) || empty($email) || empty($senha)) {
        $message = "Erro: Por favor, preencha todos os campos obrigatórios (Nome, E-mail, Senha).";
        $message_type = "error";
    } else {
        // 3. Hashing da senha
        $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

        // 4. Verifica se o e-mail já existe
        $check_sql = "SELECT id FROM $table_name WHERE email = ?";
        $stmt_check = $conn->prepare($check_sql);
        
        if ($stmt_check === false) {
             $message = "Erro na preparação da consulta de verificação: " . $conn->error;
             $message_type = "error";
        } else {
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $stmt_check->store_result();
            
            if ($stmt_check->num_rows > 0) {
                $message = "Erro: Este e-mail já está cadastrado.";
                $message_type = "error";
            } else {
                // 5. Insere novo usuário
                $sql = "INSERT INTO $table_name (nome, email, senha_hash, pergunta_seguranca, resposta_seguranca, nome_pet, especie, idade_pet) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);

                if ($stmt === false) {
                    $message = "Erro na preparação da consulta de inserção: " . $conn->error;
                    $message_type = "error";
                } else {
                    $stmt->bind_param("sssssssi", $nome, $email, $senha_hash, $pergunta, $resposta, $nome_pet, $especie, $idade);

                    if ($stmt->execute()) {
                        $message = "Cadastro realizado com sucesso! Bem-vindo(a) à Pet-code.";
                        $message_type = "success";
                    } else {
                        $message = "Erro ao cadastrar: " . $stmt->error;
                        $message_type = "error";
                    }
                    $stmt->close();
                }
            }
            $stmt_check->close();
        }
    }
} 

// Processamento do Formulário de Login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {

    $email_login = $_POST['email_login'] ?? '';
    $senha_login = $_POST['senha_login'] ?? '';

    $sql = "SELECT id, nome, senha_hash FROM $table_name WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        $message = "Erro na preparação da consulta de login: " . $conn->error;
        $message_type = "error";
    } else {
        $stmt->bind_param("s", $email_login);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verifica a senha
            if (password_verify($senha_login, $user['senha_hash'])) {
                $message = "Login realizado com sucesso! Olá, " . htmlspecialchars($user['nome']) . ".";
                $message_type = "success";
                // --- AQUI VOCÊ DEVE INICIAR A SESSÃO DE LOGIN ---
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nome'] = $user['nome'];
                // Redirecionar para a página principal após o login é uma boa prática:
                // header("Location: index.php"); 
                // exit();
                // ----------------------------------------------------

            } else {
                $message = "Erro de Login: Senha incorreta.";
                $message_type = "error";
            }
        } else {
            $message = "Erro de Login: E-mail não encontrado.";
            $message_type = "error";
        }
        $stmt->close();
    }
}

// Fecha a conexão com o banco de dados no final do script.
$conn->close();

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro | Pet-code</title>
    <link rel="icon" href="Imagens/novo-logo.png" type="image/png">
    <link rel="stylesheet" href="Css/cadastrostyle.css">
</head>
<body>
    <!-- Cabeçalho -->
    <header class="header">
        <div class="container">
            <a href="index.html" class="navbar-brand">
                <img class="pet__logo" src="Imagens/novo-logo.png" alt="Logo Pet">
                <h1 class="logo">Pet-code</h1>
            </a>
            <nav class="nav">
                <ul>
                    <li><a href="index.html">Início</a></li>
                    <li><a href="servicos.html">Serviços</a></li>
                    <li><a href="cadastro.html">Cadastrar</a></li>
                    <li><a href="comunidade.html">Comunidade</a></li>
                    <li><a href="contato.html">Contato</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="container">

        <?php if (!empty($message)): ?>
            <div class="message-<?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Seção Cadastro -->
        <section class="secao-cadastro">
            <h2>Crie sua conta na Pet-code 
                <img src="Imagens/icons8-cachorro-novo-cadastro.png" alt="Ícone dog cadastro">
            </h2>
            <p>Cadastre-se para acessar todos os recursos da plataforma e interagir com outros tutores!</p>

            <form class="form-cadastro" action="#" method="POST">

                <h3>
                    <img src="Imagens/icons8-pessoa-do-sexo-masculino-64-cadastro.png" alt="Ícone person cadastro">
                    Dados do Usuário
                </h3>
                <label for="nome">Nome completo:</label>
                <input type="text" id="nome" name="nome" placeholder="Digite seu nome completo" required>

                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" placeholder="Digite seu e-mail" required>

                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" placeholder="Crie uma senha" required>

                <label for="confirmar_senha">Confirme sua senha:</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Confirme sua senha" required>

                <h3>
                    <img src="Imagens/icons8-desbloquear-48.png" alt="Ícone cadeado">
                    Pergunta de segurança
                </h3>
                <label for="pergunta">Escolha uma pergunta:</label>
                <select id="pergunta" name="pergunta" required>
                    <option value="">Selecione...</option>
                    <option value="animal">Qual foi o nome do seu primeiro animal de estimação?</option>
                    <option value="escola">Qual era o nome da sua escola primária?</option>
                    <option value="cidade">Em que cidade você nasceu?</option>
                </select>

                <label for="resposta">Resposta:</label>
                <input type="text" id="resposta" name="resposta" placeholder="Digite sua resposta" required>

                <h3>
                    <img src="Imagens/icons8-dog-48.png" alt="Ícone dog">
                    Dados do Pet (opcional)
                </h3>
                <input type="text" name="nome_pet" placeholder="Nome do pet">
                <input type="text" name="especie" placeholder="Espécie (ex: cão, gato)">
<<<<<<< HEAD
                <input type="number" name="idade" placeholder="Idade do pet (em anos)" min="0" max="20">
=======
                <input type="number" name="idade" placeholder="Idade do pet" min="0">
>>>>>>> 2489eb1ae8cd4b77731ff014041f14ab4f2a7f5a

                <button type="submit" class="btn btn-principal">Cadastrar</button>
                <p class="texto-login">Já tem uma conta? <a href="#">Faça login</a></p>
            </form>
        </section>

        <!-- Seção Login -->
        <section class="secao-login">
            <h2>Entrar na sua conta</h2>
            <form class="form-login" action="#" method="POST">

                <label for="email_login">E-mail:</label>
                <input type="email" id="email_login" name="email_login" placeholder="Digite seu e-mail" required>

                <label for="senha_login">Senha:</label>
                <input type="password" id="senha_login" name="senha_login" placeholder="Digite sua senha" required>

                <button type="submit" class="btn btn-secundario">Entrar</button>

                <p>Ou entre com sua conta do Google:</p>
                <button type="button" class="btn-google">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google" width="20">
                    Entrar com o Google
                </button>

                <p class="esqueceu-senha"><a href="#">Esqueceu sua senha?</a></p>
            </form>
        </section>
    </main>

    <!-- Rodapé -->
    <footer class="footer">
        <p><strong>Projeto Acadêmico UMC - Pet-code</strong> | Mogi das Cruzes - SP</p>
        <p>Integrantes: Ana Julia Pinheiro da Silva, Giovanni Almeida Santos, Evelyn Kraus dos Santos.</p>
        <p>&copy; 2025 Pet-code | Incentivando a Conscientização e o Cuidado Animal.</p>
    </footer>
</body>
</html>
