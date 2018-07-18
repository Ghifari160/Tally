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
  function tally_modal_createUI(title, bodyEl, footerEl)
  {
    var ret = "<header class=\"header\">"
            + "<div class=\"title\">" + title + "</div>"
            + "</header>"
            + "<div class=\"body\">" + bodyEl + "</div>";

    if(typeof footerEl != "undefined")
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
  // @param     bool    includeConfigs    Include configuration. Default:
  // `true` @table
  // true => include configuration keys
  // false => do not include configuration keys
  // @return    string                    @table
  // The list in JSON => if the list is not empty
  // Emtpy string => if the list is empty
  function tally_get_list_JSON(includeConfigs)
  {
    var obj = {};

    obj.version = "2.0";
    obj.namespace = "com.ghifari160.tally.list.json";

    obj.tally = {};
    obj.tally.version = "1.0";
    obj.tally.dev = true;

    obj.list = [];

    if(typeof includeConfigs == "undefined")
      includeConfigs = true;

    list.children().each(function()
    {
      var item = {};

      // Skip configuration keys
      if(!includeConfigs && $(this).find(".identifier").html().substring(0, 21)
          == "com.ghifari160.tally.")
        return;

      item.identifier = punycode.toAscii($(this).find(".identifier").html());
      item.value = punycode.toAscii($(this).find(".value").html());

      obj.list.push(item);
    });

    return obj.list.length > 0 ? JSON.stringify(obj) : "";
  }

  // Gets the list in CSV
  // @return  string            The list in CSV
  function tally_get_list_csv()
  {
    var csv = "identifier,value\n";

    list.children().each(function()
    {
      // Skip configuration keys
      if($(this).find(".identifier").html().substring(0, 21)
          == "com.ghifari160.tally.")
        return;

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
      // Skip configuration keys
      if($(this).find(".identifier").html().substring(0, 21)
          == "com.ghifari160.tally.")
        return;

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
      // Skip configuration keys
      if($(this).find(".identifier").html().substring(0, 21)
          == "com.ghifari160.tally.")
        return;

      sql += "INSERT INTO " + options.name + " VALUES('"
           + punycode.toAscii($(this).find(".identifier").html())
           + "','" + punycode.toAscii($(this).find(".value").html()) + "');\n";
    });

    return sql;
  }

  // Downloads Blob
  // @param   string    ext     Extension of the file name.
  // @param   Blob      blob    Blob to be downloaded.
  function tally_downloadBlob(ext, blob)
  {
    var url, link;

    url = URL.createObjectURL(blob);
    link = document.createElement("a");

    // Download from Blob URL in supported browsers
    if(link.download !== undefined)
    {
      link.setAttribute("href", url);
      link.setAttribute("download", options.name + "." + ext);
      link.style = "visibility:hidden;";

      // Download the Blob
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
    // Open the Blob in a new tab
    else
      window.open(url, "tally_export");
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
        uri = b64 != "" ? b64 : "/";

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
          - ($(".app-body").find("h1.name").length > 0 ?
              $(".app-body h1.name").outerHeight(true) : 0)
          - $(".app-body .tally-input").outerHeight(true)
          - $(".app-body .tally-list .ui.top").outerHeight(true)
          - $(".app-footer").outerHeight(true) + "px"
    });
  }

  window._tally = {};

  var tally_menu_options = [],
      tally_menu_export = [];

  // Enqueue options menu item
  // @param   string      label                   Label of the config field.
  // @param   string      type                    Input element type.
  // @param   string      placeholder             Place holder text for the
  // input element.
  // @param   string      domClass                DOM class of the menu item.
  // `opt` will be prepended.
  // @param   ref:func    callback                Callback function of the menu
  // item. Options menu callback is called on loss of focus of the item.
  // @param   string      prefill_propertyName    Property name of the
  // ref:options object, which will be used to prefill the input element of this
  // item.
  function tally_enqueue_menu_options(label, type, placeholder, domClass,
      callback, prefill_propertyName)
  {
    var menu = {};
    menu.label = label;
    menu.type = type;
    menu.placeholder = placeholder;
    menu.domClass = "opt " + domClass;
    menu.callback = callback;
    menu.prefill_propertyName = prefill_propertyName;

    tally_menu_options.push(menu);
  }

  // Enqueues export menu item
  // @param   string      label       Menu item label.
  // @param   string      domClass    DOM class of the menu item.
  // @param   ref:func    callback    Callback of the menu item. This is
  // called when the item is clicked.
  function tally_enqueue_menu_export(label, domClass, callback)
  {
    var menu = {};
    menu.label = label;
    menu.domClass = "btn " + domClass;
    menu.callback = callback;

    tally_menu_export.push(menu);
  }

  // Get options menu items
  function tally_get_menu_options()
  {
    var stack = "";

    for(var i = 0; i < tally_menu_options.length; i++)
    {
      stack += "<div class=\"" + tally_menu_options[i].domClass + "\">"
             + "<div class=\"label\">" + tally_menu_options[i].label + "</div>"
             + "<input ";
      stack += tally_menu_options[i].type != "checkbox"
          && tally_menu_options[i].type != "radio" ? "placeholder=\""
          + tally_menu_options[i].placeholder + "\"" : "";
      stack += " type=\"" + tally_menu_options[i].type + "\""
             + " autocomplete=\"off\" autocorrect=\"off\">"
             + "</div>\n";
    }

    return stack;
  }

  // Get export menu items
  function tally_get_menu_export()
  {
    var ret = "";

    for(var i = 0; i < tally_menu_export.length; i++)
    {
      ret += "<div class=\"" + tally_menu_export[i].domClass + "\">"
           + tally_menu_export[i].label + "</div>\n";
    }

    return ret;
  }

  // Get options menu item prefill string
  // @param   string    domClass    DOM class of the menu item to prefill.
  function tally_menu_prefill_options(domClass)
  {
    var ret = "";

    for(var i = 0; i < tally_menu_options.length; i++)
    {
      if(tally_menu_options[i].domClass == domClass && ret.length == 0
          && options[tally_menu_options[i].prefill_propertyName]
          != defaults[tally_menu_options[i].prefill_propertyName])
        ret = options[tally_menu_options[i].prefill_propertyName];
    }

    return ret;
  }

  // Call options menu item callback
  // @param   string    domClass    DOM class of the menu item.
  // @param   ref:var   param       Parameter to pass to the callback function.
  function tally_menu_callback_options(domClass, param)
  {
    for(var i = 0; i < tally_menu_options.length; i++)
    {
      if(tally_menu_options[i].domClass == domClass)
        tally_menu_options[i].callback(param);
    }
  }

  // Calls export menu item callback
  // @param     string      domClass    DOM class of the menu item.
  // @param     ref:var     param       Parameter to pass down to the callback
  // function.
  function tally_menu_callback_export(domClass, param)
  {
    for(var i = 0; i < tally_menu_export.length; i++)
    {
      if(tally_menu_export[i].domClass == domClass)
        tally_menu_export[i].callback(param);
    }
  }

  // Expose certain tally options
  window._tally = {
    tally_enqueue_menu_options: tally_enqueue_menu_options,
    tally_enqueue_menu_export: tally_enqueue_menu_export,
    tally_update_item: tally_update_item,
    tally_get_list_JSON: tally_get_list_JSON,
    tally_get_list_base64: tally_get_list_base64
  };

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
    });

    tally_enqueue_menu_options("List Name", "text",
        "Tally.Dev by Ghifari160", "name", function(p)
        {
          if(p != "")
          {
            tally_update_item("com.ghifari160.tally.name", "=" + p);

            if($(".app-body").find("h1.name").length > 0)
              $(".app-body").find("h1.name").html(p);
            else
            {
              $(".app-body").prepend("<h1 class=\"name\">" + p + "</h1>");
            }

            options.name = p;
            document.title = p + " | "
                + $("meta[name=application-name]").attr("content");
          }
          else
          {
            tally_update_item("com.ghifari160.tally.name", "-1");
            $(".app-body").find("h1.name").remove();
            options.name = "";
            document.title = $("meta[name=application-name]").attr("content");
          }
        }, "name");
    tally_enqueue_menu_options("Starting Value:", "tel", "1",
       "startVal", function(p)
       {
         // Set the option if the input is not emptu and is an int
         if(p != "" && !isNaN(p))
         {
           tally_update_item("com.ghifari160.tally.startVal", "=" + p);
           options.startVal = parseInt(p);
         }
         else
         {
           tally_update_item("com.ghifari160.tally.startVal", "-1");
           options.startVal = defaults.startVal;
         }
       }, "startVal");
    tally_enqueue_menu_options("com.ghifari160.tally.upDelta:",
       "tel", "1", "upDelta", function(p)
       {
         if(p != "" && !isNaN(p))
         {
           tally_update_item("com.ghifari160.tally.upDelta", "=" + p);
           options.listUpDelta = parseInt(p);
         }
         else
         {
           tally_update_item("com.ghifari160.tally.upDelta", "-1");
           options.listUpDelta = defaults.listUpDelta;
         }
       }, "listUpDelta");
    tally_enqueue_menu_options("com.ghifari160.tally.downDelta:",
       "tel", "1", "downDelta", function(p)
       {
         if(p != "" && !isNaN(p))
         {
           tally_update_item("com.ghifari160.tally.downDelta", "=" + p);
           options.listDownDelta = parseInt(p);
         }
         else
         {
           tally_update_item("com.ghifari160.tally.downDelta", "-1");
           options.listDownDelta = defaults.listDownDelta;
         }
       }, "listDownDelta");

    tally_enqueue_menu_export("Excel", "xlsx", function(p)
    {
      xl.generate(tally_get_list_JSON(false), function(blob)
      {
        tally_downloadBlob("xlsx", blob);
      });
    });
    tally_enqueue_menu_export("CSV", "csv", function(p)
    {
      var ext, blob;

      ext = "csv";
      blob = new Blob([tally_get_list_csv()],
        {type: "text/csv;charset=utf-8;"});

      tally_downloadBlob(ext, blob);
    });
    tally_enqueue_menu_export("TSV", "tsv", function(p)
    {
      var ext, blob;

      ext = "tsv";
      blob = new Blob([tally_get_list_tsv()],
          {type: "text/tsv;charset=utf-8;"});
    });
    tally_enqueue_menu_export("JSON", "json", function(p)
    {
      var ext, blob;

      ext = "json";
      blob = new Blob([tally_get_list_json()],
          {type: "application/json;charset=utf-8;"});
    });
    tally_enqueue_menu_export("SQL", "sql", function(p)
    {
      var ext, blob;

      ext = "tsv";
      blob = new Blob([tally_get_list_sql()],
          {type: "application/sql;charset=utf-8;"});
    });

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
               + tally_get_menu_export()
               + "</div>";
      }
      else if($(this).hasClass("options"))
      {
        title = "Options";
        bodyEl = "<div class=\"container\">"
               + tally_get_menu_options()
               + "</div>";
      }

      tally_modal_createUI(title, bodyEl);

      // Prefill the options UI
      if($(this).hasClass("options"))
      {
        var domClasses = [];
        $("#modal-dialog .body .container").children().each(function()
        {
          domClasses.push($(this).attr("class"));
        });

        for(var i = 0; i < domClasses.length; i++)
        {
          $("#modal-dialog .body .container ." + domClasses[i].replace(" ", ".")
          + " input").val(tally_menu_prefill_options(domClasses[i]));
        }
      }

      tally_modal_toggle();
    });

    // Handle export UI
    $("body").on("click", "#modal-dialog .body .container .btn", function(e)
    {
      tally_menu_callback_export($(this).attr("class"), "");
    });

    // Handle options UI
    $("body").on("focusout click", "#modal-dialog .body .container .opt input",
    function(e)
    {
      var val = "";

      // Set val as the check status if input is checkbox or radio
      if($(this).attr("type") == "checkbox" || $(this).attr("type") == "radio")
        val = $(this).is(":checked")
      // Exit the handler if the input is not a checkbox or radio and the event
      // type is click
      else if(e.type == "click")
        return;
      // Set val as the value of this input
      else
        val = $(this).val();

      tally_menu_callback_options($(this).parent().attr("class"),
          val);

      tally_body_resize();
      tally_update_instanceURI();
    });
  });
}) ( jQuery );
