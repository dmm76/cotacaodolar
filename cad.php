<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Convertendo Moedas com Bacen - v2.0 (cURL)</title>
</head>
<body>
    <header>
        <h1>Resultado da Conversão - v2.0 com Bacen (cURL)</h1>
    </header>
    <main>
        <?php
            $numero = $_GET["numero"] ?? '';
            $numero = trim(str_replace(',', '.', $numero));

            if ($numero === '' || !is_numeric($numero)) {
                echo "<p style='color: red;'>Erro: Por favor, informe um valor numérico válido.</p>";
            } else {
                function buscarCotacaoBacen($data) {
                    $url = 'https://olinda.bcb.gov.br/olinda/servico/PTAX/versao/v1/odata/CotacaoDolarDia(dataCotacao=@dataCotacao)?@dataCotacao=\'' . $data . '\'&$format=json';

                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Windows local (em produção, ideal é true)

                    $resposta = curl_exec($ch);

                    if (curl_errno($ch)) {
                        echo "<p style='color:red;'>Erro ao buscar a cotação: " . curl_error($ch) . "</p>";
                        curl_close($ch);
                        return false;
                    }
                    curl_close($ch);

                    $dados = json_decode($resposta, true);
                    if (empty($dados['value'])) {
                        return false;
                    }

                    return (float)$dados['value'][0]['cotacaoVenda'];
                }

                // Começa pela data de hoje
                $data = date('Y-m-d'); // Formato ISO correto para hoje
                $dataParaApi = date('m-d-Y', strtotime($data)); // Formato para API Bacen
                $cotacao = buscarCotacaoBacen($dataParaApi);

                // Se não encontrar cotação (feriado/fds), busca os dias anteriores
                $tentativas = 5;
                while ($cotacao === false && $tentativas > 0) {
                    $data = date('Y-m-d', strtotime('-1 day', strtotime($data)));
                    $dataParaApi = date('m-d-Y', strtotime($data)); // Atualiza para novo dia
                    $cotacao = buscarCotacaoBacen($dataParaApi);
                    $tentativas--;
                }

                if ($cotacao === false) {
                    echo "<p style='color: red;'>Não foi possível obter a cotação recente do dólar.</p>";
                } else {
                    $resultado = $numero / $cotacao;
                    $formatado = number_format($resultado, 2, '.', '');

                    $dataFormatada = date('d/m/Y', strtotime($data));

                    echo "<p>Cotação utilizada (última disponível): <strong>R$ " . number_format($cotacao, 2, ',', '.') . "</strong></p>";
                    echo "<p>Data da cotação: <strong>$dataFormatada</strong></p>";
                    echo "<p>O valor convertido é: <strong>U$ $formatado</strong></p>";
                }
            }
        ?>
        <p><a href="javascript:history.go(-1)">Voltar à página anterior</a></p>
    </main>
</body>
</html>
