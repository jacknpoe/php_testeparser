<!DOCTYPE html>
<html lang="pt-BR">
	<head>
		<title>Teste de Classe \jacknpoe\ParserEndereco (para fisco em NF-e)</title>
		<link rel="stylesheet" href="php_testeparser.css"/>
		<link rel="icon" type="image/png" href="php_testeparser.png"/>
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
	<body>
		<?php
			header( "Content-Type: text/html; charset=ISO-8859-1", true);

			define( "EST_NENHUM",  'Nenhum endereço enviado até o momento!');
			define( "EST_VAZIO",   'O endereço enviado está vazio!');
			define( "EST_ENVIADO", 'Endereço enviado e processado.');
			define( "RESULTADO",   'Resultado');
			define( "LOGRADOURO",  'Logradouro');
			define( "NUMERO",      'Número');
			define( "COMPLEMENTO", 'Complemento');
			define( "PROCESSAR",   'Processar');
			define( "ENDERECO",    'Endereço (sem CEP):');
			define( "GITHUB",      'Repositório no GitHub');

			$endereco = '';
			$resultado = '';
			$estado = EST_NENHUM;

			if( isset( $_POST[ 'processar']))
			{
				$endereco = trim( $_POST[ 'endereco']);

				if( strlen( $endereco) > 0)
				{
					require_once( 'ParserEndereco.php'); // salve o código de http://pastebin.com/ypLiC6Nv com esse nome.
					$parser = new \jacknpoe\ParserEndereco();
					$parser->reconhecerEndereco( $endereco);

					$resultado = '<h1>' . RESULTADO . ': <br></h1>' . 
						'<p>' . LOGRADOURO . ': '  . htmlspecialchars( $parser->logradouro, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "ISO-8859-1")  . '</p>' .
						'<p>' . NUMERO . ': '      . htmlspecialchars( $parser->numero, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "ISO-8859-1")      . '</p>' .
						'<p>' . COMPLEMENTO . ': ' . htmlspecialchars( $parser->complemento, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "ISO-8859-1") . '</p>';
					$estado = EST_ENVIADO;	
				}
				else
				{
					$estado = EST_VAZIO;
				}
			}
		?>
		<h1><?php echo $estado; ?><br></h1>

		<form action="php_testeparser.php" method="POST" style="border: 0px">
			<p><?php echo ENDERECO; ?> <input type="text" name="endereco" value="<?php echo htmlspecialchars( $endereco, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, "ISO-8859-1"); ?>" style="width: 500px" autofocus></p>
			<p><input type="submit" name="processar" value="<?php echo PROCESSAR; ?>"></p>
		</form>

		<br><?php echo $resultado; ?><br><br>
		<p><a href="https://github.com/jacknpoe/php_testeparser"><?php echo GITHUB; ?></a></p><br><br>
		<form action="index.html" method="POST" style="border: 0px">
			<p><input type="submit" name="voltar" value="Voltar"></p>
		</form>
	</body>
</html>