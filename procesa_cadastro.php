<?php
$servername = "localhost"; // Geralmente é 'localhost'
$username = "root"; // Ex: 'root'
$password = " "; 
$dbname = "cadastro_petcod"; 

// 1. Criar a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// 2. Verificar a conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// 3. Receber os dados do formulário via POST
$nome = $_POST['nome'];
$email = $_POST['email'];
$senha = $_POST['senha'];

// 4. CRIPTOGRAFAR A SENHA (USANDO hash)
$senha_hash = password_hash($senha, PASSWORD_DEFAULT); 
// NUNCA armazene senhas em texto puro!

// 5. Preparar a instrução SQL para inserção (Usando Prepared Statements para segurança)
$stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");

// 6. Associar as variáveis aos parâmetros da instrução
// "sss" indica que os três parâmetros são strings
$stmt->bind_param("sss", $nome, $email, $senha_hash);

// 7. Executar a instrução
if ($stmt->execute()) {
    echo "Novo usuário cadastrado com sucesso!";
} else {
    // Trata erro de email duplicado ou outros erros
    echo "Erro ao cadastrar: " . $stmt->error;
}

// 8. Fechar a instrução e a conexão
$stmt->close();
$conn->close();
?>