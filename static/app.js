(function ( $ )
{
	$(document).ready(function()
	{
		var $tallyList = $('.tally-list .list'),
			id = "api/generate.id", // ID of the current list
			isDragging = false
			isUIReady = false,
			updatingUI = false;

		// Generates a UI
		// @param title the title of UI
		// @param bodyClass a class for specific styling of the body of UI
		// @param bodyContent the body of UI
		function createUI(title, bodyClass, bodyContent)
		{
			$('#modal-dialog').html('<div class="ui exit"><a href="#">&times;</a>'
				+ '</div><div class="title">' + title + '</div><div class="body '
				+ bodyClass + '">' + bodyContent + '</div></div>');
		}

		// Creates the export UI
		function createUI_export()
		{
			createUI("Export", "export", "<div class=\"btn csv\">CSV</div><div "
				+ "class=\"btn tsv\">TSV</div><div class=\"btn sql\">SQL</div>"
				// + "<div class=\"btn xlsx\">Excel</div>"
				+ "<div id=\"g-btn\" class=\"btn gdoc\">Save to Google Drive</div>");
		}

		// Creates the options UI
		function createUI_options()
		{
			createUI("Options", "options", "<div class=\"ui name\"><div class=\""
				+ "label\">List Name:</div><input placeholder=\"Tally.Dev by Ghifari160"
				+ "\"></div><div class=\"ui startVal\"><div class=\"label\">Starting "
				+ "Value</div><input placeholder=\"1\" type=\"tel\" autocomplete=\"off\""
				+ " autocorrect=\"off\"></div>");

			// Load the stored configurations
			var name = "", startVal = "";

			$tallyList.children().each(function()
			{
				if($(this).find('.identifier').html() == "g16.tally.config.name")
					name = $(this).find('.count').html();
				else if($(this).find('.identifier').html() == "g16.tally.config.startVal")
					startVal = $(this).find('.count').html();
			});

			$('#modal-dialog .ui.name input').val(name);
			$('#modal-dialog .ui.startVal input').val(startVal);
		}

		// Creates html entry for item
		// @param identifier The identifier of item
		// @param count The number to be listed with item
		function createItem(identifier, count, hidden)
		{
			return ((!hidden) ? '<div class="item">' : '<div class="item" '
				+ 'style="display: none;">') + '<div class="identifier">' + identifier
				+ '</div><div class="count">' + count + '</div></div>';
		}

		// Encodes string into base64. Compatible with unicode characters
		// @param str String to be encoded
		function utoa(str)
		{
			return Base64.encodeURI(str);
		}

		// Creates a URI representation of $tallyList
		// @return String URI representation of $tallyList
		function getEncodedData()
		{
			var data = "", encodedData;

			// Append each entry in $tallyList into data
			$tallyList.children().each(function()
			{
				var identifier = $(this).find('.identifier').html(),
					count = $(this).find('.count').html();

				data += identifier + ":" + count + ",";
			});

			// Strip the last ',' from data and encode data with utoa()
			encodedData = utoa(data.substring(0, data.length-1));

			if($tallyList.children().length > 0)
				return encodedData;
			else
				return '/';
		};

		// Decodes the URI representation of a list and updates $tallyList with the
		// decoded data
		function getDecodedData()
		{
			updatingUI = true;
			// Send an AJAX GET request to the API backend to decode the embedded data
			ajax_callback("GET", "/api/decode.tally.encodedData."
				+ window.location.pathname.substring(1), function(data)
			{
				// Seperates each row
				var fragmentX = data.split(',');

				for(var i = 0; i < fragmentX.length; i++)
				{
					// Seperates the identifier from the count in each row
					var fragmentY = fragmentX[i].split(':');

					// Handle name configuration
					if(fragmentY[0] == "g16.tally.config.name" ||
							fragmentY[0] == "g16.tally.config.startVal")
					{
						// Update the page title
						if(fragmentY[0] == "g16.tally.config.name")
							document.title = fragmentY[1];

						// Create a hidden $tallyList entry to store the configuration
						$tallyList.append(createItem(fragmentY[0], fragmentY[1], true));
					}
					// Append an entry of each row into $tallyList
					else
						$tallyList.append(createItem(fragmentY[0], fragmentY[1], false));
				}

				if(id == "api/generate.id")
				{
					// Send an AJAX GET request to the API backend to generate a unique ID
					// for this instance
					ajax_callback("GET", "/api/generate.id", function(data)
					{
						id = data;

						// Update the UI
						updatingUI = false;
						updateUI();
					});
				}
				else
				{
					// Update the UI
					updatingUI = false;
					updateUI();
				}
			});
		}

		// AJAX wrapper for easier callback
		// @param type The type of request
		// @param url The target of AJAX request
		// @param callback The callback function to be called upon successful AJAX
		// request
		function ajax_callback(type, url, callback)
		{
			$.ajax({
				type: type,
				url: url,
				success: function(data)
				{
					callback(data);
				}
			});
		}

		// Updates the UI
		function updateUI()
		{
			// Prepare the UI
			prepUI();

			var entries = $tallyList.children();

			var configParamCounts = 0;
			entries.each(function()
			{
				if($(this).css('display') == "none")
					configParamCounts++;
			});

			// Show the export button if $tallyList is not empty and the contents of
			// $tallyList are not purely config parameters
			if(entries.length > 0 && configParamCounts != entries.length)
				$('.tally-list .ui .hidden').removeClass('hidden').addClass('shown');
			// Hide the export button if $tallyList is empty
			else
				$('.tally-list .ui .shown').removeClass('shown').addClass('hidden');
		}

		// Prepares UI
		function prepUI()
		{
			if(!isUIReady && !updatingUI)
			{
				// Hide the loading UI
				hideLoadingUI(250);

				// Disable future use of this function
				isUIReady = true;
			}
		}

		// Hides the loading UI
		// @param duration the duration of the animation
		function hideLoadingUI(duration)
		{
			var count = Math.round(duration / 4), i = count,
				interval = setInterval(function()
			{
				if(i > 1)
					$('#loading').css({ 'opacity': 1 / count * i });
				else
				{
					$('#loading').remove();
					clearInterval(interval);
				}

				i--;
			}, 1);
		}

		// Call getDecodedData() if the URI is not empty and the URI is not '#'
		if(window.location.pathname.length > 1
			&& window.location.pathname.substring(1) != "#")
			getDecodedData();
		// Send an AJAX GET request to the API backend to generate a unique ID for
		// this instance
		else
		{
			ajax_callback("GET", "/api/generate.id", function(data)
			{
				id = data;
				prepUI();
			});
		}

		// Event handler to decrease the count of the clicked/tapped item
		$('body').on('click touchend', '.tally-list .list .item', function()
		{
			// Exit the handler if touchmove was fired
			if(isDragging)
				return;

			var count = $(this).find('.count').html(), startVal = 1;

			// Load the stored config
			$tallyList.children().each(function()
			{
				if($(this).find('.identifier').html() == "g16.tally.config.startVal")
					startVal = $(this).find('.count').html();
			});

			// Decrease the count of this if it is greater than one, otherwise
			// remove it from $tallyList
			if(count > startVal)
				$(this).find('.count').html(--count);
			else
				$(this).remove();

			// Update the UI to reflect the changes to $tallyList
			updateUI();

			// If $tallyList is not empty, update the current URL to represent
			// $tallyList, otherwise update the current URL to represent a blank list
			if($tallyList.children().length > 0)
			{
				window.history.pushState({ g16App: "tallyCounter" }, "",
					getEncodedData());
			}
			else
				window.history.pushState({ g16App: "tallyCounter" }, "", "/");
		});

		// Event handler to add an entry to $tallyList
		$('body').on('keypress', '.tally-input .item-input', function(e)
		{
			// If enter key is pressed and $('.tally-input .item-input') is not
			// empty
			if(e.keyCode == 13 && $('.tally-input .item-input').val() != "")
			{
				var completed = false, startVal = 1;

				// Perform a sequential search to find an entry that matches the value
				// of $('.tally-input .item-input')
				$tallyList.children().each(function()
				{
					// Increase the count of this if its identifier matches the value of
					// $('.tally-input .item-input')
					if(!completed
						&& $(this).find('.identifier').html()
						== $('.tally-input .item-input').val())
					{
						var count = $(this).find('.count').html();

						$(this).find('.count').html(++count);

						// End the search
						completed = true;
					}
					else if($(this).find('.identifier').html() == "g16.tally.config.startVal")
						startVal = $(this).find('.count').html();
				});

				// Prepend an entry of $('.tally-input .item-input') if the sequential
				// search found no match
				if(!completed)
				{
					$tallyList.prepend(createItem($('.tally-input .item-input').val(),
						startVal, false));
				}

				// TODO: Sort the list

				// Update the UI to reflect the changes made to $tallyList
				updateUI();
				// Clear $('.tally-input .item-input')
				$('.tally-input .item-input').val('');
				// Update the current URL to represent $tallyList
				window.history.pushState({ g16App: "tallyCounter"}, "", getEncodedData());
			}
		});

		// Keyboard shortcuts handler
		$('body').on('keydown', function(e)
		{
			// Trigger a click event on the app text logo if Alt+N is pressed
			if(e.altKey && e.keyCode == 78)
				$('.g16-header .g16-logo-container .g16-text a').click();
			// Trigger a click event on the export button if Ctrl+S is pressed
			else if(e.ctrlKey && e.keyCode == 83)
			{
				if($tallyList.children().length > 0)
					$('.tally-list .ui .export').click();
				// TODO: Alert tht there is nothing to export in modal mode
			}
			// Trigger a click event on the options button if Alt+O is pressed
			else if(e.altKey && e.keyCode == 79)
				$('.tally-list .ui .config').click();
			// Exit the function to allow normal key events to occur
			else
				return;

			// Prevent default key events on shortcut keys
			e.preventDefault();
		});

		// Event handler to create a new list
		$('body').on('click', '.g16-header .g16-logo-container .g16-text a,'
			+ '.g16-header .g16-logo-container .g16-logo', function(e)
		{
			ajax_callback("GET", "/api/environment.tally.name", function(title)
			{
				// Trigger a click event on the modal dialog
				$('#modal').click();

				// Clear $tallyList
				$tallyList.html('');
				// Reset the page title
				document.title = title;
				// Update the UI to reflect the changes made to $tallyList
				updateUI();
				// Update the current URL to represent a blank list
				window.history.pushState({ g16App: "tallyCounter" }, "", "/");
			});

			// Prevent default page navigation
			e.preventDefault();
		});

		// Event handler to prepare for export
		$('body').on('click', '.tally-list .ui .export', function(e)
		{
			// Create the UI
			createUI_export();
			// Show the export UI in modal mode
			$('#modal').show();
			$('#modal-dialog').show();

			// Create a "Save to Google Drive" button
			gapi.savetodrive.render('g-btn',
			{
				"src": "/export/" + getEncodedData() + "/csv/" + id + ".csv",
				"filename": id + ".csv",
				"sitename": "Tally by Ghifari160"
			});

			// Remove the styling of the rendered button to match the rest of the
			// export UI
			setTimeout(function()
			{
				$('#g-btn').removeAttr('style').css(
				{
					'background': 'transparent',
					'border-style': 'none'
				}).find('iframe').removeAttr('style');
			}, 1000);
			var count = 0,
				interval = setInterval(function()
				{
					$('#g-btn').removeAttr('style').css(
					{
						'background': 'transparent',
						'border-style': 'none'
					}).find('iframe').removeAttr('style');

					if(count < 5)
						console.log("Inverval:", ++count);
					else
						clearInterval(interval);
				}, 1000);

			// Prevent default page navigation
			e.preventDefault();
		});

		// Event handler to export data with the export UI
		$('body').on('click', '#modal-dialog .body .btn', function()
		{
			// Insert encoded $tallyList data to the hidden form
			$('#export_encodedData').val(getEncodedData());

			// Insert the export metadata to the hidden form
			$('#export_btnClass').val($(this).attr("class"));
			$('#export_form').attr("action", "/export/" + getEncodedData()
				+ '/' + $(this).attr("class").substring(4)
				+ '/' + id + '.' + $(this).attr("class").substring(4));

			// Submit the hidden form and begin export
			$('#export_form').submit();
		});

		// Event handler to prepare the app for configurations
		$('body').on('click', '.tally-list .ui .config', function(e)
		{
			// Create the options UI
			createUI_options();
			// Show the UI in modal mode
			$('#modal').show();
			$('#modal-dialog').show();

			// Prevent default page navigation
			e.preventDefault();
		});

		// Event handler for app configuration
		$('body').on('keyup', '#modal-dialog .body .ui input', function()
		{
			// Handle list name change
			if($(this).parent().hasClass('name'))
			{
				// Remove the name configuration entry from $tallyList
				$tallyList.children().each(function()
				{
					if($(this).find('.identifier').html() == "g16.tally.config.name")
						$(this).remove();
				});

				// Update the page title if it is not empty
				if($(this).val() != "")
				{
					document.title = $(this).val();

					// Create a hidden $tallyList entry to store the configuration
					$tallyList.prepend(createItem("g16.tally.config.name", $(this).val(),
						true));
				}
				// Restore the default page title if it is empty
				else
				{
					ajax_callback("GET", "/api/environment.tally.name", function(title)
					{
						document.title = title;
					});
				}
			}
			// Handle startVal change
			if($(this).parent().hasClass('startVal'))
			{
				// Remove the name configuration entry from $tallyList
				$tallyList.children().each(function()
				{
					if($(this).find('.identifier').html() == "g16.tally.config.startVal")
						$(this).remove();
				});

				// Set the startVal if input is not empty and is an integer
				if($(this).val() != "" && $(this).val() == parseInt($(this).val(), 10))
				{
					// Create a hidden $tallyList entry to store the config
					$tallyList.prepend(createItem("g16.tally.config.startVal",
						$(this).val(), true));
				}
			}

			// Update the current page URL to refelct $tallyList
			window.history.pushState({ g16App: "tallyCounter"}, "", getEncodedData());
		});

		// Event handler to hide any UI in modal mode
		$('body').on('click touchend', '#modal', function()
		{
			// Exit the handler if touchmove was fired
			if(isDragging)
				return;

			$('#modal-dialog .ui.exit a').click();
		});

		$('body').on("click", "#modal-dialog .ui.exit a", function(e)
		{
			$('#modal-dialog').hide();
			$('#modal').hide();

			// Prevent default page navigation
			e.preventDefault();
		});

		// Event handler to cancel all touch events on touchmove
		$('body').on("touchmove touchstart", function()
		{
			isDragging = !isDragging;
		});
	});
})( jQuery );
