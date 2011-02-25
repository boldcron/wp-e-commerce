<?php

$_GET["sessionid"] = $_GET["sessionid"]=="" ? $_SESSION["cobredireto_id"] : $_GET["sessionid"];
print_r($_GET["sessionid"]);

$nzshpcrt_gateways[$num] = array(
    'name'            => 'BoldCron',
    'internalname'    => 'cobredireto',
    'function'        => 'gateway_cobredireto', // Funcao do submit na loja
    'form'            => "form_cobredireto", // Formulario de config
    'submit_function' => "submit_cobredireto", // Submit do form de config
    );

if( get_option('transact_url')=="http://".$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"]){ 


function capturar($codpedido, $status){  

	global $wpdb;

	// Aqui você tem o id do pedido, o valor e o status.  
	// Confira o valor com o que você tem no seu banco de dados  
	// e, se o status for 0, libere o pedido.  

	switch($status)
	{
		case 0: 	$processed = 2;	break;
		case 1: 	$processed = 4;	break;
		default: 	$processed = 1;
 	}
	
	$sql = "update `".$wpdb->prefix."purchase_logs` set processed=$processed WHERE `sessionid`= ".$codpedido;
	$wpdb->get_row( $sql,ARRAY_A ) ;
	exit;
	
}  

if($_POST){include('boldcron/retorno.php');}

}


function gateway_cobredireto($seperator, $sessionid)
{
		global $wpdb, $wpsc_cart;
	
		// Incluindo o arquivo da biblioteca
		include('boldcron/pagamento.php');  

		// Criando uma nova compra, já com o código do pedido
		$pg=new Pg($sessionid);
			//url_erro($valor);
			$pg->url_recibo(get_option('transact_url'));
			$pg->url_retorno(get_option('transact_url'));


		$purchase_log_sql = "SELECT * FROM `".WPSC_TABLE_PURCHASE_LOGS."` WHERE `sessionid`= ".$sessionid." LIMIT 1";
		$purchase_log = $wpdb->get_results($purchase_log_sql,ARRAY_A) ;

		$cart_sql = "SELECT * FROM `".WPSC_TABLE_CART_CONTENTS."` WHERE `purchaseid`='".$purchase_log[0]['id']."'";
		$cart = $wpdb->get_results($cart_sql,ARRAY_A) ;
	
		foreach((array)$cart as $item) 
		{
			$product_data = $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."product_list` WHERE `id`='".$item['prodid']."' LIMIT 1",ARRAY_A);
			$product_data = $product_data[0];

			$produtos[] = array(
				"descricao" => stripslashes($product_data['name']).stripslashes($variation_list),
				"valor" => $item['price'],
				"quantidade" => $item['quantity'],
				"id" => uniqid());							
			// Adicionando produto
			$pg->adicionar($produtos);
		}				
		
		$_SESSION['cobredireto_id'] = $sessionid;
		// Cria a compra junto ao CobreDireto e redireciona o usuário  
		var_dump($pg->pagar()); 
		// Esvazia o carrinho 
		$wpsc_cart->empty_cart();
		exit;		
}

/**
 * Cria o formulário de cadastro de opções do módulo de pagamento
 *
 * @return void
 */
function form_cobredireto()
{
  ob_start();
?>

<tr>
  <td>E-mail</td>
  <td><input type="text" name="wpe_cobredireto_email" value="<?php echo get_option('wpe_cobredireto_email') ?>" size="20" /></td>
</tr>
<tr>
  <td>Loja</td>
  <td><input type="text" name="wpe_cobredireto_loja" value="<?php echo get_option('wpe_cobredireto_loja') ?>" size="20" /></td>
</tr>
<tr>
  <td>Usuário</td>
  <td><input type="text" name="wpe_cobredireto_usuario" value="<?php echo get_option('wpe_cobredireto_usuario') ?>" size="20" /></td>
</tr>
<tr>
  <td>Senha</td>
  <td><input type="password" name="wpe_cobredireto_senha" value="get_option('wpe_cobredireto_senha');" size="20" /></td>
</tr>
<!--
<tr>
  <td>
    Formas de pagamento:<br />
    <em>* Devem estar habilitados no painel de controle do CobreDireto para funcionar</em>
  </td>
  <td>
    <strong>Cartões de Crédito</strong><br />
    <?php foreach (array('visa3dc' => 'Visa VBV', 'redecard_mastercard' => 'Mastercard Komerci', 'redecard_diners' => 'Diners Komerci', 'amex_webpos2p' => 'Amex WebPOS 2P',) as $key => $value): ?>
    <input type="checkbox" value="<?php echo $key ?>" name="wpe_cobredireto_tipos[]" <?php checked(in_array( $key, (array) get_option('wpe_cobredireto_tipos') ), true) ?> />
    <?php echo $value ?><br />
    <?php endforeach ?>

    <strong>Débito/transferência online</strong><br />
    <?php foreach (array('bradesco_transfer' => 'Bradesco', 'itau' => 'Itaú', 'bb' => 'Banco do Brasil', 'unibanco' => 'Unibanco', 'real' => 'Real', 'banrisul_pgta' => 'Banrisul',) as $key => $value): ?>
    <input type="checkbox" value="<?php echo $key ?>" name="wpe_cobredireto_tipos[]" <?php checked(in_array( $key, (array) get_option('wpe_cobredireto_tipos') ), true) ?>/>
    <?php echo $value ?><br />
    <?php endforeach ?>

    <strong>Boleto bancário</strong><br />
    <?php foreach (array('bradesco' => 'Bradesco', 'itau' => 'Itaú', 'bb' => 'Banco do Brasil', 'unibanco' => 'Unibanco', 'real' => 'Real',) as $key => $value): ?>
    <input type="checkbox" value="<?php echo $key ?>" name="wpe_cobredireto_tipos2[]" <?php checked(in_array( $key, (array) get_option('wpe_cobredireto_tipos2') ), true) ?>/>
    <?php echo $value ?><br />
    <?php endforeach ?>
  </td>
</tr>
-->

<?php
  $return = ob_get_contents(); ob_end_clean();
  return $return;
}

/**
 * Esta função roda ao dar um submit no formulário gerado pela função form_cobredireto
 *
 * @return void 
 */

function submit_cobredireto()
{
  global $wpdb;
  foreach (array('email', 'loja', 'usuario', 'senha', 'tipos', 'tipos2') as $item) {
    $item = "wpe_cobredireto_$item";
    $data = isset($_POST[$item]) ? $_POST[$item] : '';
    if( ($item=="senha" && $data!="") || $item!="senha"){update_option($item, $data);}
  }

}

