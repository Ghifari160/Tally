(function( $ )
{
  var id = "";

  // Handle common response
  // @internal
  // @param   ref:object    objResp     REST API response object.
  function __handle_response(objResp)
  {
    var error = false,
        error_code = 0,
        error_message = "";

    if(objResp.response.error_code || objResp.response.error_message)
      error = true;

    // Parse error code
    if(objResp.response.error_code)
      error_code = objResp.response.error_code;

    // Parse error message
    if(objResp.response.error_message)
      error_message = objResp.response.error_message;

    // Alert errors to console
    if(error)
      console.error("core-dbops", error_code, error_mesage);
    // Store ID
    else if(objResp.response.id)
    {
      id = objResp.response.id;

      var state = {
        list: "2.0",
        tally: "1.0"
      },
          uri = id != "" ? id : "/";

      // Replace URI to the instance ID of this list
      history.replaceState(state, "", uri);
    }
  }

  // Builds common request payloads
  // @internal
  function __build_req()
  {
    var objReq = {},
        strReq = "";

    objReq.user_agent = "Tally/0.1";
    objReq.client_id = "tally/dev";
    objReq.request = {};
    objReq.request.list_data = JSON.parse(window._tally.tally_get_list_JSON());

    strReq = JSON.stringify(objReq);

    return strReq;
  }

  // Stores a new list
  // @internal
  function __store_list()
  {
    $.ajax(
    {
      url: "/core-dbops/store",
      contentType: "application/json",
      data: __build_req(),
      method: "POST",
      processData: false,
      success: function(objResp)
      {
        __handle_response(objResp);
      }
    });
  }

  // Updates an existing list
  // TODO
  // @internal
  function __update_list()
  {
    console.warn("\"id\" is not empty (\"" + id + "\"). Update function is not "
        + "implemented.");
  }

  // Internal update action handler
  // @internal
  function _update_list()
  {
    // Store a new list if id field is empty
    if(id.length < 1)
      __store_list();
    // Update an existing list if id field is not empty
    else
      __update_list();
  }

  var update_queue;

  // Updates list
  function update_list()
  {
    // Exit if DBOps storage is not enabled for this list
    if(!window._tally.options.dbopsStore)
      return;

    // Reset delay
    clearTimeout(update_queue);

    // Execute update after 2 seconds of delay
    update_queue = setTimeout(function()
    {
      _update_list();
    }, 2000);
  }

  $(document).ready(function()
  {
    window._tally.tally_register_action("list_update", update_list);

    window._tally.tally_enqueue_menu_options("com.ghifari160.tally.dbops.store",
        "checkbox", "", "dbops-store", function(p)
        {
          window._tally.tally_update_item("com.ghifari160.tally.dbops.store",
              "=true");
          window._tally.options.dbopsStore = true;
        }, "");
  });
}) ( jQuery );
