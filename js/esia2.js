// ВХОД ЧЕРЕЗ ЕСИА

$(function(){	
	
	if ( $('#phone').size() > 0 )
	{
		setMask('phone','+7 (***) ***-**-**');
	}
	
	// Обработка ввода с клавиатуры
	$('body').on('keypress', function(e){
		if (e.keyCode == 13) 
		{
			if ( $('input[name="btn-reg"]').size() > 0 ) $('input[name="btn-reg"]').click();
		}
	});
	
	// Регистрация
	$('body').on('click', '.btn-reg', function(){
		var error = new Error( $('.form-reg') );
		error.clear();
		
		var loader = new Loader( $('.form-reg .btn-reg') );	
		
		var options = {
			url: '/esia2/ajax/esia.php',
			type: 'POST',
			data: {action: 'reg', code: $('.rescode').val(), state: $('.resstate').val()},
			dataType: 'json',
			beforeSend: function(){
				loader.show();
			},
			success: function(data) {	

				loader.clear();
				
				// Ошибки ввода данных
				if (data.answer == 'error')
				{
					error.show(data.text);
				}
				// Успешная регистрация и авторизация
				else if (data.answer == 'success')
				{
					location.href = "/";
				}
				// Пользователь существует в системе: авторизация
				else if (data.answer == 'auth')
				{								
					location.href = "/";
				}
				// Ошибка регистрации
				else if (data.answer == 'fatal')
				{
					$('.b-form').css({'display': 'none'});
					
					var html = '<div class="b-content-subtitle">' + data.text + '</div>';
					$('.b-form').after(html);
				}				
			},
			complete: function(){
				loader.clear();
			}
		};

		$(".form-reg").ajaxSubmit(options);		
	});
})