(function( $ )
{
	$(document).ready(function()
	{
		var $tallyList = $('.tally-list .list'),
			id;

		function createItem(identifier, count)
		{
			return '<div class="item"><div class="identifier">' + identifier
				+ '</div><div class="count">' + count + '</div></div>';
		}

		function utoa(str)
		{
			return Base64.encodeURI(str);
		}

		function getEncodedData()
		{
			var data = "", encodedData;

			$tallyList.children().each(function()
			{
				var identifier = $(this).find('.identifier').html(),
					count = $(this).find('.count').html();

				data += identifier + ":" + count + ",";
			});

			encodedData = utoa(data.substring(0, data.length-1));

			return encodedData;
		}

		function ajax_callback(url, callback)
		{
			$.ajax({
				type: "GET",
				url: url,
				success: function(data)
				{
					callback(data);
				}
			});
		}

		function getDecodedData()
		{
			var data = "", interval;

			ajax_callback("/api/decode.tally.encodedData."
			+ window.location.pathname.substring(1), function(data)
			{
				var fragmentX = data.split(',');

				for(var i = 0; i < fragmentX.length; i++)
				{
					var fragmentY = fragmentX[i].split(':');
					$tallyList.append(createItem(fragmentY[0], fragmentY[1]));
				}

				updateUI();
			});
		}

		function updateUI()
		{
			if($tallyList.children().length > 0)
				$('.tally-list .ui .hidden').removeClass('hidden').addClass('shown');
			else
				$('.tally-list .ui .shown').removeClass('shown').addClass('hidden');
		}

		if(window.location.pathname.length > 1
			&& window.location.pathname.substring(1) != "#")
			getDecodedData();

		$('body').on('click touchend', '.tally-list .list .item', function()
		{
			var identifier = $(this).find('.identifer').html(),
				count = $(this).find('.count').html();

			if(count > 1)
				$(this).find('.count').html(--count);
			else
				$(this).remove();

			updateUI();

			if($tallyList.children().length > 0)
			{
				window.history.pushState({ g16App: "tallyCounter" }, "",
					getEncodedData());
			}
			else
				window.history.pushState({ g16App: "tallyCounter" }, "", "/");
		});

		$('body').on('keypress', '.tally-input .item-input', function(e)
		{
			if(e.which == 13 && $('.tally-input .item-input').val() != "")
			{
				var completed = false;
				$tallyList.children().each(function()
				{
					if(!completed
						&& $(this).find('.identifier').html()
						== $('.tally-input .item-input').val())
					{
						var identifier = $(this).find('.identifier').html(),
							count = $(this).find('.count').html();

						$(this).find('.count').html(++count);

						completed = true;
					}
				});

				if(!completed)
				{
					$tallyList.prepend(createItem($('.tally-input .item-input').val(), 1));
				}

				// TODO: Sort the list

				updateUI();
				$('.tally-input .item-input').val('');
				window.history.pushState({ g16App: "tallyCounter" }, "", getEncodedData());
			}
		});

		$('body').on('click', '.tally-list .ui .export', function()
		{
			$('#modal').show();
			$('#modal-dialog').show();

			$.ajax({
				type: "GET",
				url: "/api/generate.id",
				async: false,
				success: function(data)
				{
					id = data;
				}
			});

			gapi.savetodrive.render('g-btn',
				{
					"src": '/export/' + getEncodedData() + '/csv/' + id + '.csv',
					"filename": id + '.csv',
					"sitename": "Tally Counter by Ghifari160"
				});

			setTimeout(function()
			{
				$('#g-btn').removeAttr('style').css({
					'background': 'transparent',
					'border-style': 'none'
				}).find('iframe').removeAttr('style');
			}, 1000);
		});

		$('body').on('click touchend', '#modal', function()
		{
			$('#modal-dialog').hide();
			$('#modal').hide();
		});

		$('body').on('click', '#modal-dialog .body .btn', function()
		{
			$('#export_encodedData').val(getEncodedData());
			$('#export_btnClass').val($(this).attr('class'));
			$('#export_form').attr('action', '/export/'+ getEncodedData()
				+ '/' + $(this).attr('class').substring(4)
				+ '/' + id + '.' + $(this).attr('class').substring(4));

			$('#export_form').submit();
		});
	});
})( jQuery );
