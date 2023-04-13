<?php
	//***********************************************************************************************
	// AUTOR: Ricardo Erick Rebêlo
	// Objetivo: separar um endereço em logradouro / número / complemento
	// Obs.: tudo o que aparece após o número é considerado complemento
	// Versão Original: 01/10/2009 - Ricardo Erick Rebêlo
	// Alterações:
	// 1.0   27/06/2010 - Versão final da primeira conversão
	// 1.01  26/02/2016 - Recuperada versão mais recente de http://pastebin.com/5cqxEsAU de 15/10/2013
	//                   após perda da versão de trabalho na desinstalação do XAMPP
	// 1.1   26/02/2016 - alteração para uso em php 5.3.0 com namespace jacknpoe
	// 1.1.1 16/03/2016 - correção menor e inclusão de outros caracteres latin1 na tabela_conversao


	namespace jacknpoe;

	//***********************************************************************************************
	// constante indefinida para todas as classes de constantes - NÃO REDEFINIR
	define( "INDEFINIDO", 0);

	// informações sobre os TOKENs
	define( "TOKEN_INF_TIPO", 1);
	define( "TOKEN_INF_VALOR", 2);
	define( "TOKEN_INF_FORMATADO", 3);
	define( "TOKEN_INF_INICIO", 4);
	define( "TOKEN_INF_TAMANHO", 5);


	//***********************************************************************************************
	// Classe Parser

	class ParserEndereco
	{

		// CONSTANTES

		// versão atual da classe
		const VERSAO = '1.1.1';

		// tipos de tokens
		const TOKEN_NOME = 1;
		const TOKEN_NUMERO = 2;
		const TOKEN_N_NUMERO = 3;
		const TOKEN_DIVISOR = 25;
		const TOKEN_IND_LOG = 51;
		const TOKEN_IND_NUM = 52;
		const TOKEN_IND_COM = 53;
		const TOKEN_IND_LOC = 54;
		const TOKEN_IND_ANT = 55;

		// estados do autômato (AFD)
		const ESTADO_INICIAL = 1;
		const ESTADO_DIVISOR = 2;
		const ESTADO_N = 3;
		const ESTADO_HIFEN = 4;
		const ESTADO_NUMERO = 5;
		const ESTADO_NUMERO2 = 6;
		const ESTADO_CARACTER = 7;
		const ESTADO_NUMERO3 = 8;
		const ESTADO_CARACTER2 = 9;

		// tipos de caracteres
		const CHAR_NULO = 1;
		const CHAR_LETRA = 2;
		const CHAR_NUMERO = 3;
		const CHAR_VELHA = 4;
		const CHAR_N = 5;
		const CHAR_HIFEN = 6;
		const CHAR_DIVISOR = 7;
		const CHAR_VIRGULA = 8;


		// ATRIBUTOS

		private $_tokens;
		private $_nomes_tokens = array( 
			INDEFINIDO => '?',
			self::TOKEN_NOME => 'nome',
			self::TOKEN_NUMERO => 'número',
			self::TOKEN_N_NUMERO => 'NNúmero',
			self::TOKEN_DIVISOR => 'divisor',
			self::TOKEN_IND_LOG => 'IndLog',
			self::TOKEN_IND_NUM => 'IndNum',
			self::TOKEN_IND_COM => 'IndCom',
			self::TOKEN_IND_LOC => 'IndLoc',
			self::TOKEN_IND_ANT => 'IndAnt' );		// utilizar em registros para depuração

		public $logradouro = '';
		public $numero = '';
		public $complemento = '';


		// MÉTODOS

		function __construct()
		{
			$this->_tokens = new TokenEndereco();
		}


		function getNomeToken( $tipo)		// recupera o nome de um token para registros de depuração
		{
			return $this->_nomes_tokens[ $tipo];
		}

		function reconhecerEndereco( $endereco)		// reconhece logradouro, número e complemento
		{
			$inicio = ( $tamanho = ( $token_numero = ( $fim_logradouro = 0)));

			$endereco = trim( $endereco);

			$this->reconhecerTokens( $endereco);		// reconhece os tokens (preenche $this->_tokens)

			$n_tokens = $this->_tokens->getNumeroTokens();

			// SINTAXE / Construções Gramaticais (regras de alteração)

			for( $contador = 1; $contador <= $n_tokens; $contador ++ )		// devemos fazer as regras em todos os tokens
			{
				if( $this->_tokens->getTokenInfo( $contador, TOKEN_INF_TIPO) === self::TOKEN_NUMERO )		// coincidentemente, todas contém <num>
				{
					// regra 1   ( <num><Iant> -> <nome><nome> )
					if( $this->_tokens->getTokenInfo( $contador+1, TOKEN_INF_TIPO) === self::TOKEN_IND_ANT )
					{
						$this->_tokens->setTokenInfo( $contador, TOKEN_INF_TIPO, self::TOKEN_NOME);
						$this->_tokens->setTokenInfo( $contador+1, TOKEN_INF_TIPO, self::TOKEN_NOME);
					}

					// regras 2 e 3   (  <Iloc><num> -> <Iloc><nome>,   <Icom><num> -> <Icom><nome> )
					if( in_array( $this->_tokens->getTokenInfo( $contador-1, TOKEN_INF_TIPO), array( self::TOKEN_IND_COM, self::TOKEN_IND_LOC ), TRUE ) )
						$this->_tokens->setTokenInfo( $contador, TOKEN_INF_TIPO, self::TOKEN_NOME);

					// regra 4   ( <Inum><num> -> <Inum><Nnum> )
					if( $this->_tokens->getTokenInfo( $contador-1, TOKEN_INF_TIPO) === self::TOKEN_IND_NUM )
						$this->_tokens->setTokenInfo( $contador, TOKEN_INF_TIPO, self::TOKEN_N_NUMERO);
						
					// regra 5   ( <num><Ilog><num> -> <num><Ilog><Nnum> )
					if( $this->_tokens->getTokenInfo( $contador-1, TOKEN_INF_TIPO) === self::TOKEN_IND_LOG )
					{
						if( $this->_tokens->getTokenInfo( $contador-2, TOKEN_INF_TIPO) === self::TOKEN_NUMERO )
						{
							$this->_tokens->setTokenInfo( $contador, TOKEN_INF_TIPO, self::TOKEN_N_NUMERO);
						}
					// regra 6   ( <Ilog><num> -> <Ilog><nome> )
						else
						{
							$this->_tokens->setTokenInfo( $contador, TOKEN_INF_TIPO, self::TOKEN_NOME);
						}
					}
				}
			}

			// UMA DAS REGRAS DA SEMÂNTICA

			// primeira regra (especial: se existir TOKEN_N_NUMERO, esse é o número no endereço)
			for( $contador = 1; $contador <= $n_tokens; $contador ++ )
			{
				if( $this->_tokens->getTokenInfo( $contador, TOKEN_INF_TIPO) === self::TOKEN_N_NUMERO )
				{
					$token_numero = $contador;
					break;
				}
			}
	
			// ÚLTIMA REGRA DA SINTAXE (apenas se a primeira semântica falhar)

			if( $token_numero == 0)
			{
				for( $contador = 1; $contador <= $n_tokens; $contador ++ )
				{
					// regra 7   ( <nome><num> -> <nome><Nnum> )
					if( $this->_tokens->getTokenInfo( $contador, TOKEN_INF_TIPO) === self::TOKEN_NUMERO )
					{
						if( $this->_tokens->getTokenInfo( $contador-1, TOKEN_INF_TIPO) === self::TOKEN_NOME )
							$this->_tokens->setTokenInfo( $contador, TOKEN_INF_TIPO, self::TOKEN_N_NUMERO);
					}
				}

			// SEMÂNTICA (excetuando a primeira regra especial; principalmente exceções)

				for( $contador = $n_tokens; $contador >= 1; $contador -- )
				{
					// procuramos por um <Nnum> convertido na última regra sintática, mas da direita para a esquerda
					if( $this->_tokens->getTokenInfo( $contador, TOKEN_INF_TIPO) === self::TOKEN_N_NUMERO )
					{
						$token_numero = $contador;
						break;
					}
				}
			}

			// neste ponto, se $token_numero == 0, não existe número e apenas procuramos pelo S/N

			if( $token_numero == 0)
			{
				for( $contador = 1; $contador <= $n_tokens; $contador ++ )
				{
					if( strpos( '|SN|SNO|SNRO|', $this->_tokens->getTokenInfo( $contador, TOKEN_INF_FORMATADO)) !== FALSE )
					{
						$inicio = $this->_tokens->getTokenInfo( $contador, TOKEN_INF_INICIO);
						$tamanho = $this->_tokens->getTokenInfo( $contador, TOKEN_INF_TAMANHO);
						$fim_logradouro = ( $this->_tokens->getTokenInfo( $contador-1, TOKEN_INF_TIPO) === self::TOKEN_IND_NUM 
						                    ? $this->_tokens->getTokenInfo( $contador-1, TOKEN_INF_INICIO) : $inicio ) - 1 ;
						$token_numero = $contador;
						break;
					}

					if( strpos( '|SN|SNO|SNRO|', $this->_tokens->getTokenInfo( $contador, TOKEN_INF_FORMATADO) . 
					                             $this->_tokens->getTokenInfo( $contador+1, TOKEN_INF_FORMATADO)) !== FALSE )
					{
						$inicio = $this->_tokens->getTokenInfo( $contador, TOKEN_INF_INICIO);
						$tamanho = $this->_tokens->getTokenInfo( $contador,  TOKEN_INF_INICIO)
						         + $this->_tokens->getTokenInfo( $contador+1, TOKEN_INF_TAMANHO)
						         - $this->_tokens->getTokenInfo( $contador, TOKEN_INF_INICIO);
						$fim_logradouro = ( $this->_tokens->getTokenInfo( $contador-1, TOKEN_INF_TIPO) === self::TOKEN_IND_NUM 
						                    ? $this->_tokens->getTokenInfo( $contador-1, TOKEN_INF_INICIO) : $inicio ) - 1 ;
						$token_numero = $contador;
						break;
					}
				}

			// neste ponto, se $token_numero == 0, não existe número nem S/N

				if( $token_numero == 0)
				{
					$this->logradouro = $this->retornarSemSimbolos( $endereco);
					$this->numero = '-';
					$this->complemento = '';
					return FALSE ;
				}
			}
			else
			{
				$inicio = $this->_tokens->getTokenInfo( $token_numero, TOKEN_INF_INICIO);
				$tamanho = $this->_tokens->getTokenInfo( $token_numero, TOKEN_INF_TAMANHO);
				$fim_logradouro = ( $this->_tokens->getTokenInfo( $token_numero-1, TOKEN_INF_TIPO) === self::TOKEN_IND_NUM 
				                    ? $this->_tokens->getTokenInfo( $token_numero-1, TOKEN_INF_INICIO) : $inicio ) - 1 ;
			}

			$this->logradouro = $this->retornarSemSimbolos( substr( $endereco, 0, $fim_logradouro));
			$this->numero = $this->retornarSemSimbolos( substr( $endereco, $inicio-1, $tamanho));
			$this->complemento = $this->retornarSemSimbolos( substr( $endereco, $inicio + $tamanho-1));
	
			return TRUE;
		}

		private function retornarSemSimbolos( $texto)		// retira espaços e símbolos (ver abaixo) do início e do final de um texto
		{
			$antiga = '';
			$texto = trim( $texto);

			while( $antiga != $texto)
			{
				$antiga = $texto;

//				strpos( '|SN|SNO|SNRO|', $this->_tokens->getTokenInfo( $contador, TOKEN_INF_FORMATADO)) !== FALSE
				if( strpos( '!@"#$%-=*&,.;:/?´^~_§°', $texto[0]) !== FALSE)
					$texto = substr( $texto, 1);

				if( strpos( '!@"#$%-=*&,.;:/?´^~_§°', $texto[ strlen( $texto)-1 ]) !== FALSE)
					$texto = substr( $texto, 0, -1);  	//	$texto = substr( $texto, 0, strlen( $texto)-1);

				$texto = trim( $texto);
			}

			return $texto;
		}

		private function reconhecerTokens( $endereco)
		{
			$this->_tokens->excluirTodos();

			if( $endereco === '' or ! is_string( $endereco)) return;

			$inicio = 1;

			while( $endereco != '')
			{
				$posicao = 1;
				$estado = self::ESTADO_INICIAL;
				$tamanho = strlen( $endereco);
				$token_encontrado = FALSE;

				while( ! $token_encontrado)
				{
					$caracter = $this->avaliarCaracter( $endereco[ $posicao - 1]);

					if( $caracter !== self::CHAR_NULO )
					{
						switch( $estado)
						{
							case self::ESTADO_INICIAL:
								switch( $caracter)
								{
									case self::CHAR_DIVISOR :
									case self::CHAR_VELHA :
									case self::CHAR_VIRGULA :
										$estado = self::ESTADO_DIVISOR ;
										break;
									case self::CHAR_N :
										$estado = self::ESTADO_N ;
										break;
									case self::CHAR_HIFEN :
										$estado = self::ESTADO_HIFEN ;
										break;
									case self::CHAR_NUMERO :
										$estado = self::ESTADO_NUMERO ;
										break;
									default:
										$estado = self::ESTADO_CARACTER ;
								}
								break;

							case self::ESTADO_DIVISOR:
								if ( in_array( $caracter, array( self::CHAR_LETRA, self::CHAR_NUMERO, self::CHAR_N )))
								{
									$posicao --;
									$token_encontrado = TRUE;
								}
								break;

							case self::ESTADO_N:
							case self::ESTADO_CARACTER2:
								if ( in_array( $caracter, array( self::CHAR_LETRA, self::CHAR_N )))
								{
									$estado = self::ESTADO_CARACTER ;
								}
								else 
								{
									$posicao --;
									$token_encontrado = TRUE;
								}
								break;

							case self::ESTADO_HIFEN:
								if ( in_array( $caracter, array( self::CHAR_DIVISOR, self::CHAR_HIFEN, self::CHAR_VIRGULA )))
								{
									if ( $caracter != self::CHAR_HIFEN) $estado = self::CHAR_DIVISOR ;
								}
								else 
								{
									$posicao --;
									$token_encontrado = TRUE;
								}
								break;

							case self::ESTADO_CARACTER:
								if ( $caracter == self::CHAR_HIFEN ) $estado = self::ESTADO_CARACTER2 ;

								if ( in_array( $caracter, array( self::CHAR_DIVISOR, self::CHAR_VELHA, self::CHAR_VIRGULA )))
								{
									$posicao --;
									$token_encontrado = TRUE;
								}
								break;

							case self::ESTADO_NUMERO:
								if ( in_array( $caracter, array( self::CHAR_DIVISOR, self::CHAR_HIFEN, self::CHAR_VELHA )))
								{
									$posicao --;
									$token_encontrado = TRUE;
								}
								elseif ( $caracter != self::CHAR_NUMERO )
								{
									$estado = ( $caracter == self::CHAR_LETRA ? self::ESTADO_NUMERO3 : self::ESTADO_NUMERO2);
								}
								break;

							case self::ESTADO_NUMERO2:
								if ( in_array( $caracter, array( self::CHAR_DIVISOR, self::CHAR_HIFEN, self::CHAR_VELHA, self::CHAR_VIRGULA, self::CHAR_LETRA )))
								{
									$posicao --;
									$token_encontrado = TRUE;
								}
								else
								{
									$estado = ( $caracter == self::CHAR_NUMERO ? self::ESTADO_NUMERO : self::ESTADO_CARACTER);
								}
								break;

							case self::ESTADO_NUMERO3:
								if ( in_array( $caracter, array( self::CHAR_DIVISOR, self::CHAR_HIFEN, self::CHAR_VELHA, self::CHAR_VIRGULA)))
								{
									$posicao --;
									$token_encontrado = TRUE;
								}
								else
								{
									$estado = ( $caracter == self::CHAR_NUMERO ? self::ESTADO_NUMERO : self::ESTADO_CARACTER);
								}
								break;

							default:
								// erro (futura implementação?)
						}	// switch $estado
					}	// if $caracter nulo

					if ( ! $token_encontrado)
					{
						if ( $posicao < $tamanho)
						{
							$posicao ++;
						}
						else
						{
							$token_encontrado = TRUE;
						}
					}
				}	// while !$token_encontrado

				switch ( $estado)
				{
					case self::ESTADO_INICIAL:
					case self::ESTADO_DIVISOR:
					case self::ESTADO_HIFEN:
						$token = self::TOKEN_DIVISOR;
						break;
					case self::ESTADO_N:
					case self::ESTADO_CARACTER:
					case self::ESTADO_CARACTER2:
						$token = self::TOKEN_NOME;
						break;
					case self::ESTADO_NUMERO:
					case self::ESTADO_NUMERO2:
					case self::ESTADO_NUMERO3:
						$token = self::TOKEN_NUMERO;
						break;
					default:
						// erro (futura implementação?)
				}

				$valor = substr( $endereco, 0, $posicao);
				$formatado = $this->formatar( $valor);
				$token = $this->retornarPalavraChave( $formatado, $token);

				if ( $token != self::TOKEN_DIVISOR )
				{
					$this->_tokens->incluirToken( $token, $valor, $formatado, $inicio, $posicao);
				}

				$inicio += $posicao;
				$endereco = substr( $endereco, $posicao);
			}	// while $endereco
		}	// function
		
		private $_tabela_conversao = array(
			// C0 a FF (todas, em ordem)
			'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'AE', 'Ç'=>'C',
			'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',  'Ï'=>'I',
			'Ð'=>'D', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O',  '×'=>'x',
			'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
			'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'ae', 'ç'=>'c',
			'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',  'ï'=>'i',
			'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',	'ô'=>'o', 'õ'=>'o', 'ö'=>'o',  '÷'=>'/',
			'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'þ'=>'b',  'ÿ'=>'y',
			// A0 a BF (algumas, fora de ordem)
			'°'=>'o', 'º'=>'o', 'ª'=>'a', '¢'=>'c', '¹'=>'1', '²'=>'2', '³'=>'3',  '£'=>'L',
			'¥'=>'Y', '¦'=>'|', '§'=>'S', '©'=>'C', '­'=>'-', '®'=>'R', 'µ'=>'u'
	    	); // retirado do manual do PHP (allixsenos at gmail dot com) 
			   // alterada ordem e acrescentados alguns caracteres para compatibilizar melhor

    	private function retirarAcentosEEspeciais( $texto)
    	{
    		return strtr( $texto, $this->_tabela_conversao);	// retirado do manual do PHP (allixsenos at gmail dot com)
    	}

    	private function apenasLetrasENumeros( $texto)
		{
			$texto = $this->retirarAcentosEEspeciais( $texto);

			$texto_temp = '';

			for ( $contador = 0; $contador < strlen( $texto); $contador++ )
			{
				$ascii = ord( $texto[$contador]);

				if (    ( $ascii >= 97 and $ascii <= 122 )
				     or ( $ascii >= 65 and $ascii <= 90 )
				     or ( $ascii >= 48 and $ascii <= 57 ) )
				{
					$texto_temp .= $texto[$contador];
				}
			}

			return $texto_temp;
		}

		private function apenasLetras( $texto)
		{
			$texto = $this->retirarAcentosEEspeciais( $texto);

			$texto_temp = '';

			for ( $contador = 0; $contador < strlen( $texto); $contador++ )
			{
				$ascii = ord( $texto[$contador]);

				if (    ( $ascii >= 97 and $ascii <= 122 )
				     or ( $ascii >= 65 and $ascii <= 90 ) )
				{
					$texto_temp .= $texto[$contador];
				}
			}

			return $texto_temp;
		}

		private function apenasNumeros( $texto)
		{
			$texto_temp = '';

			for ( $contador = 0; $contador < strlen( $texto); $contador++ )
			{
				$ascii = ord( $texto[$contador]);

				if (  $ascii >= 48 and $ascii <= 57 )
				{
					$texto_temp .= $texto[$contador];
				}
			}

			return $texto_temp;
		}
		
		private $_estados = array( 'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
		                           'PB', 'PR', 'PE', 'PI', 'RR', 'RO', 'RJ', 'RN', 'RS', 'SC', 'SP', 'SE', 'TO' );
		private $_numeros = array( 'PRIMEIRA', 'SEGUNDA', 'TERCEIRA', 'QUARTA', 'QUINTA', 'SEXTA', 'SETIMA', 'OITAVA', 'NONA', 'DECIMA',
		                           'PRIMEIRO', 'SEGUNDO', 'TERCEIRO', 'QUARTO', 'QUINTO', 'SEXTO', 'SETIMO', 'OITAVO', 'NONO', 'DECIMO' );
		private $_ind_nums = array( 'N', 'NR', 'NRO', 'NO', 'NMRO', 'NUMERO', 'NUME', 'O', 'NUM', 'NMER', 'NMERU', 'NME' );
		private $_ind_coml = array( 'S', 'SL', 'SALA', 'SLA', 'ESQ', 'ESQUINA', 'AP', 'APTO', 'APT', 'CXP',
		                            'CXA', 'CAIXA', 'POSTAL', 'CX', 'CP', 'PERTO', 'PROXIMO', 'PROX', 'APAR', 'ESQU',
		                            'LOJA', 'LJ', 'L' );
		private $_ind_ante = array( 'ANDAR', 'PISO' );
		private $_ind_loca = array( 'ED', 'KM', 'EDIFICIO', 'EDIF', 'EDF', 'QUADRA', 'LOTE', 'LT', 'QD', 'BOX', 'CASA', 'GALPAO' );
		private $_ind_logr = array( 'RUA', 'R', 'BR', 'ROD', 'AVENIDA', 'AV', 'AVEN', 'EST', 'ESTRADA', 'RODOVIA',
		                            'AL', 'ALAMEDA', 'TRAV', 'TRV', 'TRAVESSA', 'PR', 'PRC', 'PRACA', 'PATIO',
		                            'ALMD', 'PRAA', 'LINHA', 'TV', 'AVDA', 'RD', 'PCA', 'PC', 'ESTADUAL', 'FEDERAL' );
		private $_ind_com5 = array( 'APART', 'ESQUI', 'PROXI' );
		private $_ind_log4 = array( 'AVEN', 'ESTR', 'TRAV', 'RODO' );

		private function retornarPalavraChave( $valor, $token_origem)
		{
			if( $token_origem == self::TOKEN_NUMERO and $valor[0] == 'N') return self::TOKEN_N_NUMERO;

			if( in_array( $valor, $this->_numeros) ) return self::TOKEN_NUMERO;
			
			if( in_array( $valor, $this->_ind_nums) ) return self::TOKEN_IND_NUM;

			if( in_array( $this->apenasLetras( $valor), $this->_ind_nums)
			    and $this->apenasNumeros( substr( $this->apenasLetrasENumeros( $valor), 0, 1)) == '' )
				return self::TOKEN_N_NUMERO;

			if( in_array( $valor, $this->_ind_coml) ) return self::TOKEN_IND_COM;

			if( in_array( $valor, $this->_ind_ante) ) return self::TOKEN_IND_ANT;

			if( in_array( $valor, $this->_ind_loca) ) return self::TOKEN_IND_LOC;

			if( in_array( $valor, $this->_ind_logr) or in_array( $valor, $this->_estados) ) return self::TOKEN_IND_LOG;

			if( in_array( substr( $valor, 0, 5), $this->_ind_com5) ) return self::TOKEN_IND_COM;

			if( in_array( substr( $valor, 0, 4), $this->_ind_log4) ) return self::TOKEN_IND_LOG;

			if( substr( $valor, 0, 4) == 'EDIF' ) return self::TOKEN_IND_LOC;

			return ( ( $token_origem == self::TOKEN_NOME and strlen( $valor) < 3 ) ? self::TOKEN_IND_COM : $token_origem );
		}	// function
		
		private function formatar( $valor)
		{
			return strtoupper( $this->apenasLetrasENumeros( $valor));
		}

		private function avaliarCaracter( $caracter)
		{
			$corrigido = strtoupper( $this->retirarAcentosEEspeciais( $caracter));
			$ascii = ord( $corrigido);

			if( $corrigido == 'N' or $corrigido == 'O') return self::CHAR_N;
			if( $caracter == ',' or $caracter == '/') return self::CHAR_VIRGULA;
			if( $ascii >= 65 and $ascii <= 90) return self::CHAR_LETRA;
			if( $ascii >= 48 and $ascii <= 57) return self::CHAR_NUMERO;
			if( strpos( '|:! ', $caracter) !== FALSE) return self::CHAR_DIVISOR;
			if( $corrigido == '-') return self::CHAR_HIFEN;
			if( $corrigido == '#') return self::CHAR_VELHA;
			return self::CHAR_NULO;
		}
	}


	//***********************************************************************************************
	// Classe com lista com as informações sobre cada Token que a análise léxica encontrar

	class TokenEndereco
	{
		// ATRIBUTOS

		private $_tokens = array();
		private $_n_tokens = 0;

		// MÉTODOS

		function getNumeroTokens()		// get do número total de Tokens armazenados
		{
			return $this->_n_tokens;
		}

		function incluirToken( $tipo, $valor, $formatado, $inicio, $tamanho)		// inclui um token ao final da lista
		{
			return $this->incluirInterno( $this->_n_tokens + 1, $tipo, $valor, $formatado, $inicio, $tamanho );
		}

		private function incluirInterno( $ordem, $tipo, $valor, $formatado, $inicio, $tamanho)		// utilizado internamente
		{
			if( $ordem < 1) return FALSE;

			$incluindo = ( $ordem > $this->_n_tokens);
			if( $incluindo) $this->_n_tokens = $ordem;

			$this->_tokens[ $ordem] = array( 1 => $tipo, $valor, $formatado, $inicio, $tamanho);

			return $incluindo;
		}

		function excluirTodos()		// exclui todos os tokens
		{
			unset( $this->_tokens);
			$this->_tokens = array();
			$this->_n_tokens = 0;
		}

		function getTokenInfo( $token, $informacao)		// recupera informação de um token (inclusive inexistente)
		{
			if( $token > 0 and $token <= $this->_n_tokens and is_array( $this->_tokens[ $token]) )
			{
				return $this->_tokens[ $token][ $informacao];
			}
			else
			{
				switch ( $informacao)
				{
					case TOKEN_INF_TIPO:
						return INDEFINIDO;

					case TOKEN_INF_VALOR:
					case TOKEN_INF_FORMATADO:
						return '';

					case TOKEN_INF_INICIO:
					case TOKEN_INF_TAMANHO:
						return 0;

					default:
						return INDEFINIDO;
				}
			}
		}

		function setTokenInfo( $token, $informacao, $valor)		// escreve informação de um token (retorna se existe o token)
		{
			if( is_array( $this->_tokens[ $token]) )
			{
				$this->_tokens[ $token][ $informacao] = $valor;
				return TRUE;
			}
			else
			{
				return FALSE;
			}
		}
	}
?>