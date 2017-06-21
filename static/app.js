(function ( $ )
{
	$(document).ready(function()
	{
		var $tallyList = $(".tally-list .list"), // The list
				id = "/api/generate.id", // ID of the list
				ajaxRequests = [], // AJAX requests queue
				timeSinceLastUpdate = 0,
				lastTitle = "";

		// AJAX wrapper for easier callback and timely execution (queue system)
		// @param type the type of request
		// @param url the target of the request
		// @param callback the callback function to be called upon successful
		// AJAX request
		// @param data the payload of request
		// @param processData indicator to process the payload before request
		// @param contentType content MIME type of the payload
		function ajax_callback(type, url, callback, data, processData,
				contentType)
		{
			data = (typeof data !== "undefined") ? data : "";
			processData = (typeof processData !== "undefined") ? processData :
					true;
			contentType = (typeof contentType !== "undefined") ? contentType :
					"application/x-www-form-urlencoded";

			// AJAX request barebone
			options = {
				type: type,
				url: url,
				success: function(response)
				{
					// AJAX queue system internal functions
					ajaxRequests.shift(); // Shift the request queue
					ajaxManagerExecuteQueue(); // Call the queue manager
					callback(response);
				}
			};

			// Data handling
			if(data.length > 0)
			{
				options.data = data;
				options.processData = processData;
			}

			if(!processData)
				options.contentType = contentType;

			// Push the request to the queue
			ajaxRequests.push(options);

			// Call the queue manager immediately if the queue only has one item
			if(ajaxRequests.length == 1)
				ajaxManagerExecuteQueue();
		}

		// AJAX queue manager
		function ajaxManagerExecuteQueue()
		{
			// Execute the first request on the queue if the queue is not empty
			if(ajaxRequests.length > 0)
				$.ajax(ajaxRequests[0]);
		}

		// Generates a UI
		// @param title the title of the UI
		// @param bodyClass a class for specific styling of the body of the UI
		// @param bodyContent the body of the UI
		function createUI(title, bodyClass, bodyContent)
		{
			$("#modal-dialog").html("<div class=\"ui exit\"><a href=\"#\">&times;"
					+ "</a></div><div class=\"title\">" + title + "</div><div class=\""
					+ "body " + bodyClass + "\">" + bodyContent + "</div></div>");
		}

		// Modal UI helper
		function UIHelper(param)
		{
			if(param == "export")
			{
				createUI("Export", "export", "<div class=\"btn csv\">CSV</div><div "
						+ "class=\"btn tsv\">TSV</div><div class=\"btn sql\">SQL</div><div "
						+ "id=\"g-btn\" class=\"btn gdoc\">Save to Google Drive</div>");
			}
			else if(param == "options")
			{
				createUI("Options", "options", "<div class=\"ui name\"><div class=\""
						+ "label\">List Name</div><input placeholder=\"Tally.Dev by "
						+ "Ghifari160\"></div><div class=\"ui startVal\"><div class=\"label"
						+ "\">Starting Value</div><input placeholder=\"1\" type=\"tel\" "
						+ "autocomplete=\"off\" autocorrect=\"off\"></div><div class=\"ui "
						+ "advanced\"><div class=\"label\"><a href=\"#\">Advanced...</a>"
						+ "</div></div>");

			}
			else if(param == "opts-adv")
			{
				createUI("Advanced Configuration", "options-adv", "<div class=\"ui "
						+ "key\"><div class=\"label\">Key</div><input placeholder=\"g16."
						+ "tally.config.startVal\"></div><div class=\"ui val\"><div class="
						+ "\"label\">Value</div><input placeholder=\"1\"></div><div class="
						+ "\"ui add\"><div class=\"btn\">Add Configuration</div></div><div "
						+ "class=\"ui remove\"><div class=\"btn\">Remove Configuration</div>"
						+ "</div><div class=\"ui conflist\"><div class=\"label\">Existing "
						+ "Configurations</div><code></code></div>");
			}
			else
			{
				createUI("Invalid UI!", "", "Invalid UI!");
			}
		}

		// Creates HTML code for the list entry
		// @param identifier the identifier of the item
		// @param count the number to be listed with the item
		// @param hidden creates a hidden item (for configuration)
		function createItem(identifier, count, hidden)
		{
			return "<div class=\"item\"" + ((!hidden) ? "" : " style=\"display: "
					+ "none;\"") + "><div class=\"identifier\">" + identifier
					+ "</div><div class=\"count\">" + count + "</div></div>";
		}

		// Encodes string into base64. Compatible with unicode characters
		// @param str string to be encoded
		function utoa(str)
		{
			// return Base64.encodeURI(str);
			return btoa(encodeURIComponent(str));
		}

		// Creates an URI representation of $tallyList
		// @return string URI representation of $tallyList
		function getEncodedData()
		{
			var data = "",
					encodedData = "";

			// Append each entry in $tallyList into data
			$tallyList.children().each(function()
			{
				var identifier = $(this).find(".identifier").html(),
						count = $(this).find(".count").html();

				data += identifier + ":" + count + ",";
			});

			// Strip the last ',' from data and encode data with utoa()
			encodedData = utoa(data.substring(0, data.length - 1));

			return encodedData;
		}

		// Decodes an URI representation of the data into $tallyList
		// @param callback an optional callback function
		function getDecodedData(callback)
		{
			// Send an AJAX GET request to the API backend to decode the embedded data
			ajax_callback("GET", "/api/decode.tally.encodedData."
					+ window.location.pathname.substring(1), function(data)
			{
				// Seperates rows of data
				var fragmentX = data.split(',');

				for(var i = 0; i < fragmentX.length; i++)
				{
					// Seperates the identifier from the count
					var fragmentY = fragmentX[i].split(':');

					// Handle configuration keys
					if(fragmentY[0].substring(0, 17) == "g16.tally.config.")
						// Create a hidden $tallyList entry to store the configuration
						$tallyList.append(createItem(fragmentY[0], fragmentY[1], true));
					else
						// Append an entry of each row into $tallyList
						$tallyList.append(createItem(fragmentY[0], fragmentY[1], false));
				}

				updateUI(callback);
			});
		}

		// Updates the UI
		// @param callback optional callback function
		function updateUI(callback)
		{
			var entries = $tallyList.children(),
					configParamCounts = 0,
					configPermalink = false,
					configTitle = "";

			entries.each(function()
			{
				if($(this).css("display") == "none")
				{
					configParamCounts++;
					if($(this).find(".identifier").html() == "g16.tally.config.permalink"
							&& $(this).find(".value").html() == "1")
						configPermalink = true;
					if($(this).find(".identifier").html() == "g16.tally.config.name")
						configTitle = $(this).find(".value").html();
				}
			});

			// Show the export button if $tallyList is not empty and its contents are
			// not purely config parameters
			if(entries.length > 0 && configParamCounts != entries.length)
				$('.tally-list .ui .hidden').removeClass('hidden').addClass('shown');
			// Hide the export button if $tallyList is empty
			else
				$('.tally-list .ui .shown').removeClass('shown').addClass('hidden');

			// Update the URI of the list
			var data = getEncodedData();
			if(configPermalink)
			{
				var temp = document.title;
				document.title = "Saving...";

				ajax_callback("POST", "/api/generate.permalink", function()
				{
					document.title = temp;
					callback();
				}, data, false, "text/csv");
			}
			else if(entries.length > 0)
			{
				window.history.pushState({ g16App: "tally" }, "", data);
				callback();
			}
			else
			{
				window.history.pushState({ g16App: "tally" }, "", "/");
				callback();
			}
		}

		// Hides the loading UI
		// @param duration the duration of the animation
		function hideLoadingUI(duration)
		{
			var count = Math.round(duration / 4), i = count,
					interval;

			$("body").css({ "overflow": "hidden" });

			interval = setInterval(function()
			{
				if(i > 1)
					$("#loading").css({ "opacity": 1 / count * i });
				else
				{
					$("#loading").remove();
					$("body").removeAttr("style");
					clearInterval(interval);
				}

				i--;
			}, 1);
		}

		// Call getDecodedData() if the URI is not empty and the URI is not "#"
		if(window.location.pathname.length > 1
				&& window.location.pathname.substring(1) != "#")
		{
			getDecodedData(function()
			{
				// Check for permalink
				var permalink = false;
				$tallyList.children().each(function()
				{
					if($(this).find(".identifier").html() == "g16.tally.config.permalink"
							&& $(this).find(".count").html() == "1")
						permalink = true;
				});

				// Acqure a new ID if the list is not permalink-enabled
				if(!permalink)
				{
					ajax_callback("GET", "/api/generate.id", function(data)
					{
						id = data;
						hideLoadingUI(250);
					});
				}
				else
				{
					id = window.location.pathname.substring(1);
					hideLoadingUI(250);
				}
			});
		}
		else
		{
			ajax_callback("GET", "/api/generate.id", function(data)
			{
				id = data;
				hideLoadingUI(250);
			});
		}

		// Event Handler: add an entry to $tallyList
		$("body").on("keypress", ".tally-input .item-input", function(e)
		{
			// If enter key is pressed and $(".tally-input .item-input") is not empty
			if(e.keyCode == 13 && $(".tally-input .item-input").val() != "")
			{
				var completed = false, startVal = 1;

				// Sequential search to find an entry that matches the value of
				// $(".tally-input .item-input")
				$tallyList.children().each(function()
				{
					// Increase the count of this if its identifier matches the value of
					// $(".tally-input .item-input")
					if(!completed && $(this).find(".identifier").html().toLowerCase() ==
							$(".tally-input .item-input").val().toLowerCase())
					{
						var count = $(this).find(".count").html();

						$(this).find(".count").html(++count);

						// End the search
						completed = true;
					}
				});

				// Prepend an entry of $(".tally-input .item-input") if the Sequential
				// search found no match
				if(!completed)
				{
					$tallyList.prepend(createItem($(".tally-input .item-input").val(),
							startVal, false));
				}

				$(".tally-input .item-input").val("");

				updateUI();
			}
		});

		// Event Handler: decrease the count of an entry
		$("body").on("click", ".tally-list .list .item", function()
		{
			var count = $(this).find(".count").html(), startVal = 1;

			// Load the stored config key for startVal
			$tallyList.children().each(function()
			{
				if($(this).find(".identifier").html() == "g16.tally.config.startVal")
					startVal = $(this).find(".count").html();
			});

			// Decrease the count of this entry if it is greater than startVal,
			// otherwise remove it from $tallyList
			if(count > startVal)
				$(this).find(".count").html(--count);
			else
				$(this).remove();

			updateUI();
		});

		// Event Handler: modal UI engine
		$("body").on("click", "#modal, #modal-dialog .ui.exit a", function(e)
		{
			$("#modal-dialog").hide().html("");
			$("#modal").hide();

			e.preventDefault();
		});

		// Event Handler: modal UI inner-linking
		$("#modal-dialog").on("click", ".body .ui a", function(e)
		{
			// // Handle options link
			// if($(this).hasClass("config"))
			// 	$(".tally-list .ui a.config").trigger("click");
			// // Handle export link
			// else if($(this).hasClass("export"))
			// 	$(".tally-list .ui a.export").trigger("click");
			// Handler advanced options link
			// else if($(this).hasClass("advanced"))
			if($(this).parent().parent().hasClass("advanced"))
			{
				UIHelper("opts-adv");

				// Update config list
				var ch = $tallyList.children();
				for(var i = 0; i < ch.length; i++)
				{
					var key = $(ch[i]).find(".identifier").html(),
							val = $(ch[i]).find(".count").html();

					if(key.length > 16 && key.substring(0, 17) == "g16.tally.config.")
					{
						$("#modal-dialog .body .conflist code").append("<details><summary>"
								+ key + "</summary><table style=\"margin: 0 auto\"><tr><td "
								+ "style=\"text-align: right;\">Key:</td><td style=\"text-align:"
								+ " left;\">" + key + "</td></tr><tr><td style=\"text-align: "
								+ "right;\">Value:</td><td style=\"text-align: left;\">" + val
								+ "</td></tr></table></details>");
					}
				}
			}
			else
				UIHelper();
		});

		// Event Handler: modal UI
		$(".body .options .ui, .tally-list .ui").on("click", "a", function(e)
		{
			// Handle options link
			if($(this).hasClass("config"))
			{
				UIHelper("options");

				// Load the stored configurations
				$tallyList.children().each(function()
				{
					if($(this).find(".identifier").html() == "g16.tally.config.name")
						$("#modal-dialog .ui.name input").val($(this).find(".count").html());
					else if($(this).find(".identifier").html() ==
							"g16.tally.config.startVal")
					{
						$("#modal-dialog .ui.startVal .input").val($(this).find(".count")
								.html());
					}
				});
			}
			// Handle export link
			else if($(this).hasClass("export"))
			{
				UIHelper("export");

				// Create a "Save to Google Drive" button
				gapi.savetodrive.render("g-btn",
				{
					"src": "/export/" + getEncodedData() + "/csv/" + id + ".csv",
					"filename": id + ".csv",
					"sitename": "Tally by Ghifari160"
				});

				// Remove the styling of the rendered button to mathc the rest of the
				// export UI
				var count = 0,
						interval = setInterval(function()
						{
							$("#g-btn").removeAttr("style").css(
							{
								"background": "transparent",
								"border-style": "none"
							}).find("iframe").removeAttr("style");

							if(count > 4)
								clearInterval(interval);
							else
								console.log("[UI Cleaner]", ++count);
						}, 1000);
			}
			else
				UIHelper();

			$("#modal").show();
			$("#modal-dialog").show();

			e.preventDefault();
		});
	});
})( jQuery );
