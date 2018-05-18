(function ( $ )
{
  var options = {};

  var defaults = {
    startVal: 1,
    listUpDelta: 1,
    listDownDelta: 1,
    name: ""
  };

  var list;

  // Checks the existence of item
  // @param   string    identifier    The identifier to look for.
  // @return  bool                    `true` => item exist. `false` => item
  // does not exist.
  function tally_isItemExistent(identifier)
  {
    var list = $(".app-body .tally-list .list");
    var ret = false;

    list.children().each(function()
    {
      if(!ret && $(this).find(".identifier").html() == identifier)
        ret = true;
    });

    return ret;
  }

  // Generates item element
  // @param   string    identifier    The identifier of the item.
  // @param   string    value         The value of the item.
  // @return  string                  The item element.
  function tally_generate_item(identifier, value)
  {
    var ret = "";

    if(identifier.substring(0,21) == "com.ghifari160.tally." ||
       identifier.substring(0,17) == "g16.tally.config.")
      ret += "<div class=\"item hidden\">";
    else
      ret += "<div class=\"item\">";

    ret += "<div class=\"identifier\">" + identifier + "</div>"
         + "<div class=\"value\">" + value + "</div>"
         + "</div>";

    return ret;
  }

  // Updates item on the list
  // @param   string    identifier    The identifier to update.
  // @param   string    deltaVal      The value change. @table
  // `+n` => add `n` to value
  // `-n` => subtract `n` from value
  // `=n` => set `n` as the value
  function tally_update_item(identifier, deltaVal)
  {
    var done = false;

    list.children().each(function()
    {
      if($(this).find(".identifier").html() == identifier)
      {
        var val = parseInt($(this).find(".value").html());

        // Update value based on deltaVal
        switch(deltaVal.substring(0, 1))
        {
          case "-":
            val -= parseInt(deltaVal.substring(1));
            break;

          case "+":
            val += parseInt(deltaVal.substring(1));
            break;

          case "=":
          default:
            val = deltaVal.substring(1);
        }

        // Set the item value if val >= options.startVal
        if(val >= options.startVal)
        {
          $(this).find(".value").html(val);
        }
        // Remove the item if val < options.startVal
        else
          $(this).remove();

        done = true;
      }
    });

    // If the item is non-existent and the deltaVal is a setting deltaVal,
    // create an item.
    if(!done && deltaVal.substring(0, 1) == "=")
      list.append(tally_generate_item(identifier, deltaVal.substring(1)));

    tally_update_ui();
  }

  // Handles item input
  // @param   string    identifier    The identifier to handle.
  function tally_handle_item(identifier)
  {
    // If item is non-existent, just add it to the list as is.
    if(!tally_isItemExistent(identifier))
      tally_update_item(identifier, "=" + options.startVal);
    else
      tally_update_item(identifier, "+" + options.listUpDelta);
  }

  // Repositions the modal dialog
  function tally_modal_reposition()
  {
    // Centers the dialog
    $("#modal-dialog").css(
    {
      "top": ($(window).outerHeight() - $("#modal-dialog").outerHeight()) / 2 + "px",
      "bottom": ($(window).outerHeight() - $("#modal-dialog").outerHeight()) / 2 + "px",
      "left": ($(window).outerWidth() - $("#modal-dialog").outerWidth()) / 2 + "px",
      "right": ($(window).outerWidth() - $("#modal-dialog").outerWidth()) / 2 + "px"
    });
  }

  var tally_modal_state = false;
  // Toggles the modal display
  function tally_modal_toggle()
  {
    if(!tally_modal_state)
    {
      // Show both modal elements
      $("#modal").css({"display": "block"});
      $("#modal-dialog").css({"display": "table"});

      // Expands the body
      $("#modal-dialog").find(".body").css(
      {
        "height": $("#modal-dialog").outerHeight() -
            $("#modal-dialog").find(".header").outerHeight() -
            $("#modal-dialog").find(".footer").outerHeight() -
            ($("#modal-dialog").find(".body").outerHeight() -
            $("#modal-dialog").find(".body").height()) + "px"
      });

      // Reposition the UI
      tally_modal_reposition();
    }
    else
    {
      // Hide both modal elements
      $("#modal").css({"display": "none"});
      $("#modal-dialog").css({"display": "none"});
    }

    // Switch the state
    tally_modal_state = !tally_modal_state;
  }

  // Creates a modal UI
  // @param   string    title     The title of the dialog.
  // @param   string    bodyEl    The HTML string of the dialog body.
  // @param   string    footerEl  The HTML string of the dialog footer.
  function tally_modal_createUI(title, bodyEl, footerEl = null)
  {
    var ret = "<header class=\"header\">"
            + "<div class=\"title\">" + title + "</div>"
            + "</header>"
            + "<div class=\"body\">" + bodyEl + "</div>";
    if(footerEl != null)
      ret += "<footer class=\"footer\">" + footerEl + "</footer>";

    $("#modal-dialog").html(ret);
  }

  // Gets the list length
  // @param   bool    includeConfig   @table
  // `true` => include configuration keys in the total count
  // `false` => ignore configuration keys from the count
  // @return  int                     The length of the list
  function tally_list_length(includeConfig)
  {
    var l = 0;

    list.children().each(function()
    {
      var identifier = $(this).find(".identifier").html();

      if(identifier.substring(0, 21) == "com.ghifari160.tally."
          && includeConfig)
        l++;
      else if(identifier.substring(0, 21) != "com.ghifari160.tally.")
        l++;
    });

    return l;
  }

  // Updates list UI
  function tally_update_ui()
  {
    var l = tally_list_length(false),
        exportUI = $(".app-body .tally-list .ui.top").find(".export");

    if(l > 0)
      exportUI.parent().removeClass("hidden");
    else
      exportUI.parent().addClass("hidden");
  }

  // Gets the list in JSON
  // @return    string            @table
  // The list in JSON => if the list is not empty
  // Emtpy string => if the list is empty
  function tally_get_list_JSON()
  {
    var obj = {};

    obj.version = "2.0";

    obj.tally = {};
    obj.tally.version = "1.0";
    obj.tally.dev = true;

    obj.list = [];

    list.children().each(function()
    {
      var item = {};
      item.identifier = punycode.toAscii($(this).find(".identifier").html());
      item.value = punycode.toAscii($(this).find(".value").html());

      obj.list.push(item);
    });

    return (obj.list.length > 0) ? JSON.stringify(obj) : "";
  }

  // Gets the list in CSV
  // @return  string            The list in CSV
  function tally_get_list_csv()
  {
    var csv = "identifier,value\n";

    list.children().each(function()
    {
      csv += $(this).find(".identifier").html() + ","
           + $(this).find(".value").html() + "\n";
    });

    return csv;
  }

  // Gets the list in TSV
  // @return  string            The list in TSV
  function tally_get_list_tsv()
  {
    var tsv = "identifier\tvalue\n";

    list.children().each(function()
    {
      tsv += $(this).find(".identifier").html() + "\t"
           + $(this).find(".value").html() + "\n";
    });

    return tsv;
  }

  // Gets the list in SQL
  // @return  string            The list in SQL
  function tally_get_list_sql()
  {
    var sql = "CREATE TABLE " + options.name + " (\n"
            + "\tidentifier text,\n"
            + "\tvalue int\n"
            + ");\n";

    list.children().each(function()
    {
      sql += "INSERT INTO " + options.name + " VALUES('"
           + punycode.toAscii($(this).find(".identifier").html())
           + "','" + punycode.toAscii($(this).find(".value").html()) + "');\n";
    });

    return sql;
  }

  // Sets the list from JSON
  // @param   string    json    A valid JSON string of a Tally list (list v2.0)
  // @return  bool              @table
  // `true` => the list was successfully set
  // `false` => the list cannot be set
  function tally_set_list_JSON(json)
  {
    var obj = {};
    try
    {
      obj = JSON.parse(json);
    }
    catch(e)
    {
      return tally_set_list_legacy(json);
    }

    // Incompatible version
    if(obj.version != "2.0")
      return false;

    // Set the list
    for(var i = 0; i < obj.list.length; i++)
    {
      var item = obj.list[i];

      tally_update_item(punycode.toUnicode(item.identifier), "="
          + punycode.toUnicode(item.value));
    }

    // Count the length of the list
    var l = 0;
    list.children().each(function()
    {
      l++;
    });

    // Check the integrity of the list
    if(l != obj.list.length)
      return false;

    return true;
  }

  function tally_set_list_legacy(legacy)
  {
    var csl = legacy.split(",");

    for(var i = 0; i < csl.length; i++)
    {
      var ic = csl[i].split(":");

      if(ic.length == 2)
        tally_update_item(ic[0], "=" + ic[1]);
      else
        return false;
    }

    var l = 0;
    list.children().each(function()
    {
      l++
    });

    if(l != csl.length)
      return false;

    return true;
  }

  // Sets the list from Base64
  // @param   string    base64    Base64-encoded JSON string of a Tally list
  // (v2.0)
  // @return  bool                @table
  // `true` => the list was successfully set
  // `false` => the list cannot be set
  function tally_set_list_base64(base64)
  {
    return tally_set_list_JSON(Base64.decode(base64));
  }

  // Gets the list in Base64
  // @return    string            Base64-encoded JSON of the list.
  function tally_get_list_base64()
  {
    return Base64.encodeURI(tally_get_list_JSON());
  }

  // Updates the URI of the current instance
  function tally_update_instanceURI()
  {
    var state = {
      list: "2.0",
      tally: "1.0"
    };

    var b64 = tally_get_list_base64(),
        uri = (b64 != "") ? b64 : "/";

    history.replaceState(state, "", uri);
  }

  // Scans options from the current list.
  // @warn Use sparingly!
  function tally_scan_options()
  {
    list.children().each(function()
    {
      var identifier = $(this).find(".identifier").html();

      // List v2.0
      if(identifier.substring(0,21) == "com.ghifari160.tally.")
      {
        var key = identifier.substring(21),
            val = $(this).find(".value").html();

        switch(key)
        {
          case "name":
            options.name = val;
            break;

          case "startVal":
            options.startVal = val;
            break;

          case "upDelta":
            options.listUpDelta = val;
            break;

          case downDelta:
            options.listDownDelta = val;
            break;
        }
      }
      // List v1.0 (Legacy)
      else if(identifier.substring(0, 17) == "g16.tally.config.")
      {
        var key = identifier.substring(17),
            val = $(this).find(".value").html();

        switch(key)
        {
          case "name":
            options.name = val;
            break;

          case "startVal":
            options.startVal = val;
            break;
        }
      }
    });

    if(options.name != defaults.name)
    {
      if($(".app-body").find("h1.name").length > 0)
        $(".app-body h1.name").html(options.name);
      else
        $(".app-body").prepend("<h1 class=\"name\">" + options.name + "</h1>");
    }
  }

  // Resizes the app body
  function tally_body_resize()
  {
    $(".app-body .tally-list").css(
    {
      "max-height": $(window).outerHeight()
          - $(".app-header").outerHeight(true)
          - (($(".app-body").find("h1.name").length > 0) ?
              $(".app-body h1.name").outerHeight(true) : 0)
          - $(".app-body .tally-input").outerHeight(true)
          - $(".app-body .tally-list .ui.top").outerHeight(true)
          - $(".app-footer").outerHeight(true) + "px"
    });
  }

  $(document).ready(function()
  {
    list = $(".app-body .tally-list .list");

    defaults.name = $("meta[name=application-name]").attr("content");
    $.extend(options, defaults);

    // Instance info
    var current_path = window.location.pathname,
        tally_reload = false,
        tally_reload_success = false;

    // Attempt to reload a previous state from the URI
    if(current_path.length > 1)
    {
      cpath = current_path.substring(1);
      tally_reload = true;
      tally_reload_success = tally_set_list_base64(cpath);
    }

    // Functions to execute if the current instance is a reload of a previous
    // state
    if(tally_reload)
    {
      // Scan options from the list
      tally_scan_options();

      // Functions to execute if the reload was successful
      if(tally_reload_success)
        tally_update_instanceURI();
    }

    // Sets the default max-height
    tally_body_resize();

    // Prepend the modal divs
    $("body").prepend("<div id=\"modal\"></div><div id=\"modal-dialog\"></div>");

    // Window resize handler
    $(window).on("resize", function()
    {
      tally_modal_reposition();
      tally_body_resize();
    });

    $("body").on("keypress", ".app-body .tally-input .item-input", function(e)
    {
      // Handle enter/return
      if(e.keyCode == 13 && $(".app-body .tally-input .item-input").val() != "")
      {
        // Handle items
        tally_handle_item($(".app-body .tally-input .item-input").val());

        $(".app-body .tally-input .item-input").val("");

        tally_update_instanceURI();
      }
    });

    // Handle clicks/taps on list item
    $("body").on("click touchend", ".app-body .tally-list .list .item", function(e)
    {
      // Reduce the item if it's clicked/tapped
      tally_update_item($(this).find(".identifier").html(), "-"
          + options.listDownDelta);

      tally_update_instanceURI();
    });

    // Modal Handler
    $("body").on("click", "#modal", function(e)
    {
      // Trigger focusout on anything other than the name option UI
      $("body #modal-dialog .body .container .opt.startVal input")
        .trigger("focusout");

      tally_modal_toggle();
    })

    // UI
    $("body").on("click", ".app-body .tally-list .ui a", function(e)
    {
      var title = "",
          bodyEl = "";

      // Prevent default on proper classes
      if($(this).hasClass("export") || $(this).hasClass("options"))
        e.preventDefault();

      if($(this).hasClass("export"))
      {
        title = "Export";
        bodyEl = "<div class=\"container\">"
               + "<div class=\"btn csv\">CSV</div>"
               + "<div class=\"btn tsv\">TSV</div>"
               + "<div class=\"btn sql\">SQL</div>"
               + "</div>";
      }
      else if($(this).hasClass("options"))
      {
        title = "Options";
        bodyEl = "<div class=\"container\">"

               + "<div class=\"opt name\">"
               + "<div class=\"label\">List Name:</div>"
               + "<input placeholder=\"Tally.Dev by Ghifari160\" type=\"text\""
               + " autocomplete=\"off\" autocorrect=\"off\">"
               + "</div>"

               + "<div class=\"opt startVal\">"
               + "<div class=\"label\">Starting Value:</div>"
               + "<input placeholder=\"1\" type=\"tel\" autocomplete=\"off\" "
               + "autocorrect=\"off\">"
               + "</div>"

               + "<div class=\"opt upDelta\">"
               + "<div class=\"label\">com.ghifari160.tally.upDelta:</div>"
               + "<input placeholder=\"1\" type=\"tel\" autocomplete=\"off\" "
               + "autocorrect=\"off\">"
               + "</div>"

               + "<div class=\"opt downDelta\">"
               + "<div class=\"label\">com.ghifari160.tally.downDelta:</div>"
               + "<input placeholder=\"1\" type=\"tel\" autocomplete=\"off\" "
               + "autocorrect=\"off\">"
               + "</div>"

               + "</div>";
      }

      tally_modal_createUI(title, bodyEl);

      // Prefill the options UI
      if($(this).hasClass("options"))
      {
        if(options.name != defaults.name)
        {
          $("#modal-dialog .body .container .opt.name input")
            .val(options.name);
        }

        if(options.startVal != defaults.startVal)
        {
          $("#modal-dialog .body .container .opt.startVal input")
            .val(options.startVal);
        }

        if(options.listUpDelta != defaults.listUpDelta)
        {
          $("#modal-dialog .body .container .opt.upDelta input")
            .val(options.listUpDelta);
        }

        if(options.listDownDelta != defaults.listDownDelta)
        {
          $("#modal-dialog .body .container .opt.downDelta input")
            .val(options.listDownDelta);
        }
      }

      tally_modal_toggle();
    });

    // Handle export UI
    $("body").on("click", "#modal-dialog .body .container .btn", function(e)
    {
      var blob, url, ext;

      // CSV export
      if($(this).hasClass("csv"))
      {
        ext = "csv";
        blob = new Blob([tally_get_list_csv()],
            {type: "text/csv;charset=utf-8;"});
      }
      // TSV export
      else if($(this).hasClass("tsv"))
      {
        ext = "tsv";
        blob = new Blob([tally_get_list_tsv()],
            {type: "text/tsv;charset=utf-8;"});
      }
      // SQL export
      else if($(this).hasClass("sql"))
      {
        ext = "sql";
        blob = new Blob([tally_get_list_sql()],
            {type: "application/sql;charset=urf-8;"});
      }

      url = URL.createObjectURL(blob);
      var link = document.createElement("a");

      // Download from Blob in supported browsers
      if(link.download !== undefined)
      {
        link.setAttribute("href", url);
        link.setAttribute("download", options.name + "." + ext);
        link.style = "visibility:hidden;";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      }
      else
        window.open(url, "tally_export");
    });

    // Handle options UI
    $("body").on("focusout", "#modal-dialog .body .container .opt input",
    function(e)
    {
      // Name configuration
      if($(this).parent().hasClass("name"))
      {
        // Set the name of the list
        if($(this).val() != "")
        {
          tally_update_item("com.ghifari160.tally.name", "=" + $(this).val());

          if($(".app-body").find("h1.name").length > 0)
            $(".app-body").find("h1.name").html($(this).val());
          else
          {
            $(".app-body").prepend("<h1 class=\"name\">" + $(this).val()
                + "</h1>");
          }

          options.name = $(this).val();
          document.title = $(this).val() + " | "
              + $("meta[name=application-name]").attr("content");
        }
        // Restore the defaults if the input is empty
        else
        {
          tally_update_item("com.ghifari160.tally.name", "-1");
          $(".app-body h1.name").remove();
          options.name = "";
          document.title = $("meta[name=application-name]").attr("content");
        }
      }
      // Starting value configuration
      else if($(this).parent().hasClass("startVal"))
      {
        // Set the option of the input is not empty and is an int
        if($(this).val() != "" && !isNaN($(this).val()))
        {
          tally_update_item("com.ghifari160.tally.startVal", "="
            + $(this).val());
          options.startVal = parseInt($(this).val());
        }
        // Restore defaults if the input is empty
        else
        {
          tally_update_item("com.ghifari160.tally.startVal", "-1");
          options.startVal = defaults.startVal;
        }
      }
      // Up Delta configuration
      else if($(this).parent().hasClass("upDelta"))
      {
        if($(this).val() != "" && !isNaN($(this).val()))
        {
          tally_update_item("com.ghifari160.tally.upDelta", "="
            + $(this).val());
          options.listUpDelta = parseInt($(this).val());
        }
        else
        {
          tally_update_item("com.ghifari160.tally.upDelta", "-1");
          options.listUpDelta = defaults.listUpDelta;
        }
      }
      // Down Delta configuration
      else if($(this).parent().hasClass("downDelta"))
      {
        if($(this).val() != "" && !isNaN($(this).val()))
        {
          tally_update_item("com.ghifari160.tally.downDelta", "="
            + $(this).val());
          options.listDownDelta = parseInt($(this).val());
        }
        else
        {
          tally_update_item("com.ghifari160.tally.downDelta", "-1");
          options.listDownDelta = defaults.listDownDelta;
        }
      }

      tally_body_resize();
      tally_update_instanceURI();
    });
  });
}) ( jQuery );
