<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Вход через ЕСИА");
require_once($_SERVER["DOCUMENT_ROOT"]."/esia2/include/functions.php");
?>

<? 
// Пользователь авторизован в системе
if ( $USER->isAuthorized() ) header("location: /");

$authEsia = new AuthEsia();
if ( ($res = $authEsia->check_account()) !== true ) :
?>

<div class="b-content-subtitle"><?=$res?></div>

<? elseif ( !$authEsia->auth() ) : ?>
	
<script type="text/javascript" src="/esia2/js/esia2.js"></script>	

<div class="b-form">
	<div class="b-content-subtitle">Для доступа в систему Вам необходимо пройти процедуру регистрации.</div>
	<form class="view-label-left f-grey form-reg form-upload">

		<?$userAttr = $authEsia->getAttribute(); ?>
		<div class="b-comment">ПОЛЯ, ОТМЕЧЕННЫЕ <span class="asteriks">*</span>, ОБЯЗАТЕЛЬНЫ К ЗАПОЛНЕНИЮ!</div>

<?$_SESSION['personSNILS'] = $userAttr['personSNILS'] ?>
<?$_SESSION['userId'] = $userAttr['userId'] ?>
<?$_SESSION['assuranceLevel'] = $userAttr['assuranceLevel'] ?>
<?$_SESSION['lastName'] = $userAttr['lastName'] ?>
		<? if ( isset( $userAttr['lastName'] ) && trim( $userAttr['lastName'] ) != '') : ?>
		<label>Фамилия <span class="asteriks">*</span></label><span class="value"><?=$userAttr['lastName']?></span><br/>		
		<? endif; ?>
<?$_SESSION['firstName'] = $userAttr['firstName'] ?>
		<? if ( isset( $userAttr['firstName'] ) && trim( $userAttr['firstName'] ) != '') : ?>
		<label>Имя <span class="asteriks">*</span></label><span class="value"><?=$userAttr['firstName']?></span><br/>		
		<? endif; ?>
<?$_SESSION['middleName'] = $userAttr['middleName'] ?>
		<? if ( isset( $userAttr['middleName'] ) && trim( $userAttr['middleName'] ) != '') : ?>
		<label>Отчество <span class="asteriks">*</span></label><span class="value"><?=$userAttr['middleName']?></span><br/>		
		<? endif; ?>
<?$_SESSION['birthDate'] = $userAttr['birthDate'] ?>
		<? if ( isset( $userAttr['birthDate'] ) && trim( $userAttr['birthDate'] ) != '') : ?>
		<label>Дата рождения <span class="asteriks">*</span></label><span class="value"><?=$userAttr['birthDate']?> г.</span><br/>		
		<? endif; ?>
<?$_SESSION['gender'] = $userAttr['gender'] ?>
		<? if ( isset( $userAttr['gender'] ) ) : ?>
		<label>Пол <span class="asteriks">*</span></label><span class="value"><? if ($userAttr['gender'] == 'M') echo 'Мужской'; else echo 'Женский';?></span><br/>			
		<? endif; ?>		
<?$_SESSION['personEMail'] = $userAttr['personEMail'] ?>
		<? if ( isset( $userAttr['personEMail'] ) && trim( $userAttr['personEMail'] ) != '') : ?>
		<label>E-mail <span class="asteriks">*</span></label><span class="value"><?=$userAttr['personEMail']?></span><br/>		
		<? endif; ?>		
<?$_SESSION['personMobilePhone'] = $userAttr['personMobilePhone'] ?>
		<? if ( isset( $userAttr['personMobilePhone'] ) && trim( $userAttr['personMobilePhone'] ) != '') : ?>
		
		<?
		$personMobilePhone = $userAttr['personMobilePhone'];
		if ( preg_match('/^(8|\+7|7)/iu', $personMobilePhone) )
		{
			$personMobilePhone = preg_replace('/^(8|\+7|7)/iu', '', $personMobilePhone); 		
			$personMobilePhone = preg_replace('/[^0-9]/iu', '', $personMobilePhone);						
			$personMobilePhone = '+7' . ' ' . '(' . substr($personMobilePhone, 0, 3) . ')' . ' ' . substr($personMobilePhone, 3, 3) . '-' . substr($personMobilePhone, 6, 2) . '-' . substr($personMobilePhone, 8, 2);
		}	
		?>
		
		<label>Номер мобильного телефона <span class="asteriks">*</span></label><span class="value"><?=$personMobilePhone?></span><br/>		
		<? endif; ?>
		
		<label>Муниципальное образование <span class="asteriks">*</span></label>
		<span class='mo-trigger'><a href="#popup-mo" class="popup-trigger link-municipality u_mo" title="Выбор субъекта / муниципального образования">Выбрать муниципальное образование</a></span>
		<input type="hidden" name="u_mo" class="u_mo" value="0" /><br/>

		<div class="btn-wrapper"><input type="button" class="btn-reg" value="Регистрация"></div>
	</form>
</div>

<?=core_municipality();?>

<? 
else : 
	header("location: /");    
endif; 
?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>