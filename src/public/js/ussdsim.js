// window.$ = window.jQuery = require("./js/jquery-3.1.0.min.js");
$(document).ready(function () {
  const APP_REQUEST_INIT = $('#ussd-request-init-code').text().trim();
  const APP_REQUEST_END = $('#ussd-request-end-code').text().trim();
  const APP_REQUEST_FAILED = $('#ussd-request-failed-code').text().trim();
  const APP_REQUEST_CANCELLED = $('#ussd-request-cancelled-code').text().trim();
  const APP_REQUEST_ASK_USER_RESPONSE = $('#ussd-request-ask-user-response-code').text().trim();
  const APP_REQUEST_USER_SENT_RESPONSE = $('#ussd-request-user-sent-response-code').text().trim();

  const SIMULATOR_USSD_API_ENDPOINT = "api/v1/ussd.php"
  const simulatorData = JSON.parse($('#simulator-data').text());
  const networks = simulatorData.networks || {};
  const simulatorMetadata = ['info', 'warning', 'error']

  const ussdStringKey = $('#user-response-param-name').text().trim()
  const ussdServiceOpKey = $('#request-type-param-name').text().trim()
  const sessionIDKey = $('#session-id-param-name').text().trim()
  const msisdnKey = $('#user-phone-param-name').text().trim()
  const networkKey = $('#user-network-param-name').text().trim()
  const messageKey = $('#menu-string-param-name').text().trim()

  const parametersNames = {
    [messageKey]: "Message",
    [ussdServiceOpKey]: "Request code",
    [sessionIDKey]: "Session ID"
  }
  const requestTypes = {
    [APP_REQUEST_INIT]: "USSD session initiation",
    [APP_REQUEST_END]: "USSD session end",
    [APP_REQUEST_FAILED]: "USSD request failed. Typically, an error happened at the application's side and the response cannot be decoded by the teleco.",
    [APP_REQUEST_CANCELLED]: "USSD request cancelled, intentionally by the user or due to timeout",
    [APP_REQUEST_ASK_USER_RESPONSE]: "The USSD application is requesting for an answer from the user.",
    [APP_REQUEST_USER_SENT_RESPONSE]: "User has sent a response back to the application",
  }

  let currentRequest = null;
  let sessionID = null;

  if (!$("#endpoint").val()) {
    $("#endpoint").val(retrieve("endpoint", "http://"))
  }

  let ussdCode = $("#ussdCode").val();

  if (!ussdCode) {
    ussdCode = retrieve("ussdCode", "*380*78#");
    $("#ussdCode").val(ussdCode)
  }

  save("ussdCode", ussdCode);

  $("#dial-ussdCode-tooltip").text(ussdCode);

  if (!$("#msisdn").val()) {
    $("#msisdn").val(retrieve("msisdn", "+"))
    $("#network").val(detectNetwork($("#msisdn").val()))
  }

  $(".cancel").hide();
  $("form").submit((e) => e.preventDefault());
  $(".send").click(sendRequest);
  $(".cancel").click(cancelRequest);
  $(".toggle-controls").click(toggleControls);

  $("#ussdCode").on("input", (e) => {
    ussdCode = e.target.value;
    $("#dial-ussdCode-tooltip").text(ussdCode);
    save("ussdCode", ussdCode);
  });

  $("#dial-ussdCode-tooltip").on("input", (e) => {
    ussdCode = e.target.innerText;
    $("#ussdCode").val(ussdCode);
    save("ussdCode", ussdCode);
  });

  $("#endpoint").on("change", (e) => {
    if (!e.target.value) {
      e.target.value = "http://";
    }

    save("endpoint", e.target.value);
  });

  $("#msisdn").on("change", handleCustomPhoneNumberChange);
  $("#msisdn").on("input", handleCustomPhoneNumberChange);
  $("#msisdn").on("focus", handleCustomPhoneNumberChange);
  $("#retrieved-phone-number").on("change", handleRetrievedPhoneNumberChange);
  $("#retrieved-endpoints").on("change", handleEndpointChange);
  $("#endpoint").on("input", handleEndpointInputChange);
  $("#network").on("input", handleCustomNetworkChange)
  $("#retrieved-networks").on("change", handleRetrievedNetworkChange)

  updateWatch();
  toggleControls();
  createMetadataCards();

  function createMetadataCards() {
    for (const metadata of simulatorMetadata) {
      $('#simulator-debug').prepend(
        $(`
        <div id="simulator-${metadata}-content" style="display:none;" class="card m-1">
          <div class="card-header text-${metadata}">${metadata.toUpperCase()}</div>
          <div class="card-body"><div class="card-text"></div></div>
        </div>`)
      )
    }
  }

  function save(id, value) {
    if (window.localStorage) {
      localStorage.setItem("ussd_simulator_" + id, value);
    }
  }

  function retrieve(id, defaultValue) {
    if (window.localStorage) {
      return localStorage.getItem("ussd_simulator_" + id) || defaultValue;
    }

    return defaultValue;
  }

  function toggleControls() {
    if ($("#controls").hasClass("controls-shown")) {
      $(".toggle-controls").html("&Congruent;");
      $("#app").removeClass("shift-right");
      $("#controls").removeClass("controls-shown");
      $("#controls").addClass("controls-hidden");
    } else {
      $(".toggle-controls").html("x");
      $("#controls").removeClass("controls-hidden");
      $("#controls").addClass("controls-shown");
      $("#app").addClass("shift-right");

      $("#endpoint").focus();
      $("#endpoint").select();
    }
  }

  function lastUssdPage(message) {
    $("#ussd-popup-content").html(message);
    $("#simulator-response-input-field").hide();
    $("#simulator-response-input").val(ussdCode);
    $(".cancel").html("OK");
    $(".send").hide();
    sessionID = null;
  }

  function ussdEnd(message, description = "") {
    hideLoader();
    $("#ussd-popup").fadeIn(500);

    lastUssdPage(message);

    $("#loading").text("Request Completed.");
    const desc = description ? description : message;
    $("#simulator-debug-content > .card-body > .card-text").html(desc);
  }

  function hideLoader() {
    $("#ussd-loader").hide();
    $("#loader-spinner").removeClass("lds-ring");
  }

  function showLoader() {
    $("#loader-spinner").addClass("lds-ring");
    $("#ussd-loader").show();
  }

  function ussdAskForResponse(message) {
    $("#simulator-response-input").val("");
    $(".send").html("SEND");
    $(".send").fadeIn(300);
    $(".cancel").fadeIn(300);
    $("#simulator-response-input-field").show();

    $("#ussd-popup-content").text(message);
    $("#ussd-popup").fadeIn(500);
    $("#simulator-response-input").focus();
  }

  function parseResponse(response) {
    console.log("Response received: ", response);

    let responseToProcess = {
      response,
      data: {
        [sessionIDKey]: sessionID
      },
    };

    let parsed = {};

    if (response.constructor !== Object) {
      try {
        parsed = JSON.parse(response);
      } catch (error) {
        // Application failed
        responseToProcess.data.message =
          "Simulator could not parse the response.<br><small>The response got from the server is not a valid JSON string.<br>It typically means an error happened at the USSD application's side. Kindly read the response payload (above) to know what was the error.";
        responseToProcess.data.requestType = APP_REQUEST_FAILED;
        console.log(error);
        return responseToProcess;
      }
    }

    for (const metadata of simulatorMetadata) {
      if (parsed[metadata]) {
        responseToProcess.data[metadata] = parsed[metadata];
      }
    }

    if (parsed.message !== undefined && parsed[ussdServiceOpKey] !== undefined) {
      responseToProcess.data.requestType = `${parsed[ussdServiceOpKey]}`;
      responseToProcess.data.message = parsed.message ? `${parsed.message}` : 'No message';

      if (
        responseToProcess.data.requestType !== APP_REQUEST_ASK_USER_RESPONSE &&
        responseToProcess.data.requestType !== APP_REQUEST_END
      ) {
        // Application Failed.2
        responseToProcess.data.message =
          `Got Invalid '${ussdServiceOpKey}' from the application on th server.`;
        responseToProcess.data.requestType = APP_REQUEST_FAILED;
      }
    } else {
      responseToProcess.data.requestType = APP_REQUEST_FAILED;
      responseToProcess.data.message =
        `Response parsed successfully but does not contain expected values (${messageKey}, ${ussdServiceOpKey})`; // Application failed with invalid response.
    }

    return responseToProcess;
  }

  function processServerResponse(response) {
    hideLoader();
    $("#loading").html("Request Completed.");

    if (response !== undefined) {
      // console.log(getValidJson(response))
      const toDebug = getValidJson(response)
      if (toDebug) {
        $.each(toDebug, function (key, val) {
          if (simulatorMetadata.includes(key.toLowerCase())) {
            return
          }

          if (key === ussdServiceOpKey) {
            val = `Code: ${val}<br>Meaning of this code: ${requestTypes[val]}`
          }

          if (key in parametersNames) {
            key = parametersNames[key]
          }

          $("#simulator-debug-content > .card-body > .card-text").append($(`
            <div class="card">
                <div class="card-header text-primary"><small>${key}</small></div>
                <div class="card-body">
                    <div class="card-text"><small>${val}</small></div>
                </div>
            </div>          
          `));
        })
      } else {
        $("#simulator-debug-content > .card-body > .card-text").html(response);
        $("#simulator-debug-content").show(250)
      }
    }

    const responseToProcess = parseResponse(response);

    if (responseToProcess.data !== undefined) {
      const val = responseToProcess.data

      switch (val.requestType.toString()) {
        case APP_REQUEST_ASK_USER_RESPONSE:
          ussdAskForResponse(val.message);
          break;
        case APP_REQUEST_END:
          ussdEnd(val.message);
          $("#simulator-response-input").blur();
          break;
        case APP_REQUEST_FAILED:
          const error =
            "<span class='text-danger'>ERROR:</span><br>" + val.message;
          const debug = responseToProcess.response ?
            responseToProcess.response + "<br><br>" + error :
            error;
          ussdEnd("Application Failed.", debug);
          break;
      }

      for (const type of simulatorMetadata) {
        const data = responseToProcess.data[type]
        let html = ''
        console.log(data)

        if (data) {
          if (Array.isArray(data)) {
            for (const key in data) {
              if (data.hasOwnProperty(key)) {
                const element = data[key];
                html += `<div>${typeof element === "string" ? element : key +": "+ JSON.stringify(element)}</div>`;
              }
            }
          } else if (typeof data === "string") {
            html = data
          } else {
            html = JSON.stringify(data)
          }

          $(`#simulator-${type}-content .card-text`).html(html);
          $(`#simulator-${type}-content`).show(250)
        }
      }
    }
  }

  function clearDebugPanel() {
    $("#simulator-debug-content > .card-body > .card-text").html("")
    // $("#simulator-debug-content").hide(250)

    for (const metadata of simulatorMetadata) {
      $(`#simulator-${metadata}-content > .card-body > .card-text`).html("")
      $(`#simulator-${metadata}-content`).hide(250)
    }
  }

  function sendRequest() {
    let requestType;

    if (sessionID === null) {
      requestType = APP_REQUEST_INIT;
      sessionID = newSessionID();
      $("#simulator-response-input").val(ussdCode);
    } else {
      requestType = APP_REQUEST_USER_SENT_RESPONSE;
    }

    const userResponse = $("#simulator-response-input").val();

    if (!userResponse) {
      $("#simulator-response-input").prop('placeholder', 'Kindly input a response')
      setTimeout(() => {
        $("#simulator-response-input").prop('placeholder', '')
      }, 5000);
      return;
    }

    clearDebugPanel()

    $("#loading").html("USSD code running...");
    $("#dial-ussdCode").hide();
    $("#ussd-popup").fadeOut(50);
    showLoader();
    $(".cancel").html("CANCEL");
    $(".cancel").show();
    $(".send").hide();

    let endpoint = $("#endpoint").val();

    if (endpoint == "") {
      ussdEnd("Missing Endpoint URL.");
      return;
    }

    if (!endpoint.endsWith("/")) {
      endpoint += "/";
    }

    if (!isUrlValid(endpoint)) {
      ussdEnd("Invalid Endpoint URL.");
      return;
    }

    const msisdn = parsePhoneNumber($("#msisdn").val());
    if (!msisdn || !isValidPhoneNumber(msisdn)) {
      ussdEnd(`Invalid number "${msisdn}"`);
      return;
    }

    let network = $("#network").val();
    if (!network && msisdn) {
      network = detectNetwork(msisdn)
      if (network) {
        $("#network").val(network);
      } else {
        ussdEnd(`Missing network mnc`);
        return;
      }
    }

    const data = {
      [ussdStringKey]: encodeURIComponent(userResponse),
      [ussdServiceOpKey]: encodeURIComponent(requestType),
      [sessionIDKey]: encodeURIComponent(sessionID),
      [msisdnKey]: encodeURIComponent(msisdn),
      [networkKey]: encodeURIComponent(network),
      endpoint: encodeURIComponent(endpoint),
    };

    const failCallback = (error) => {
      let display = "";
      let description = "";

      if (error.readyState === 0) {
        const pageUrl = $('#pageUrl').text().trim();

        display = "Application not available.";
        description = `Cannot reach the endpoint: <span class='text-danger'>${endpoint}</span>
        <br><br>
        <ul>
          <li>If, using the development server shipped with the simulator, check if the url of this page (<a href="${pageUrl}">${pageUrl}</a>) is still available (simply refresh the page).</li>
          <li>Check if the ussd endpoint provided is correct.</li>
          <li>Check if the server hosting the application is running.</li>
          <li>Check if the server hosting the simulator can send request to the application's server. (Typically, you cannot call an application on your local machine, from the simulator on a remote server. In that case, the simulator has to be on your local machine too).</li>
        </ul>`;
      } else {
        display = "Application failed.";
        description = error.statusText || "";
        description += error.responseText ?
          "<br><br>" + error.responseText :
          "";
      }

      ussdEnd(display, description);

      console.error("SEND REQUEST ERROR:", error);
    };

    /**
     * Use this line instead if experiencing infinte request in google chrome.
     * currentRequest = $.post(SIMULATOR_USSD_API_ENDPOINT, data);
     */
    // currentRequest = $.post(endpoint, data);
    currentRequest = $.post(SIMULATOR_USSD_API_ENDPOINT, data);
    currentRequest.done(processServerResponse);
    currentRequest.fail(failCallback);
  }

  function cancelRequest(sendCancelRequest = true) {
    if (currentRequest) {
      currentRequest.abort();
    }

    clearDebugPanel()

    $("#simulator-response-input").blur();
    $("#ussd-popup").fadeOut(300);

    setTimeout(() => {
      hideLoader();
      if (!sendCancelRequest) {
        // This is to resolve a bug with the $("#ussd-popup").fadeOut(300); above. When this function (cancelRequest) is called alone (not called by a click event), $("#ussd-popup").fadeOut(300); does not work? Why?
        $("#ussd-popup").hide();
      }

      $(".cancel").hide();
      $(".send").html("DIAL");
      $(".send").show();
      $("#dial-ussdCode-icon").html("&#x1F4DE;");
      $("#dial-ussdCode").fadeIn(500);
      $("#loading").html("Request Cancelled.");
    }, 300);

    sessionID = null;

    if (sendCancelRequest) {
      const msisdn = $("#msisdn").val();
      const network = $("#network").val();
      const userResponse = "";
      const requestType = APP_REQUEST_CANCELLED;
      let endpoint = $("#endpoint").val();

      if (!endpoint) {
        $("#simulator-debug-content .card-text").text("Missing Endpoint URL");
        return;
      }

      if (!endpoint.endsWith("/")) {
        endpoint += "/";
      }

      if (!isUrlValid(endpoint)) {
        $("#simulator-debug-content .card-text").text("Invalid Endpoint URL");
        return;
      }

      const data = {
        [ussdStringKey]: encodeURIComponent(userResponse),
        [ussdServiceOpKey]: encodeURIComponent(requestType),
        [sessionIDKey]: encodeURIComponent(sessionID),
        [msisdnKey]: encodeURIComponent(msisdn),
        [networkKey]: encodeURIComponent(network),
      };

      const failCallback = (error) => {
        /*
        cancelledRequestCallback({
          response: "Endpoint unreachable.",
          data: error.responseText || ""
          }); */
        // console.log(error);
      };

      /**
       * Use this line instead if experiencing infinte request in google chrome.
       * currentRequest = $.post(SIMULATOR_USSD_API_ENDPOINT, data);
       */
      // currentRequest = $.post(endpoint, data);
      currentRequest = $.post(SIMULATOR_USSD_API_ENDPOINT, data);
      currentRequest.done(cancelledRequestCallback);
      currentRequest.fail(failCallback);
    }
  }

  function cancelledRequestCallback(data) {
    $("#loading").html("Request Cancelled.");

    setTimeout(() => {
      $("#ussd-popup-content").text("");
      $("#simulator-response-input-field").hide();
      $("#simulator-response-input").blur();
      $("#simulator-response-input").val(ussdCode);
    }, 100);

    if (data.hasOwnProperty("response")) {
      $("#simulator-debug-content .card-text").html(prettyJSON(data["response"]));
    }

    // for (const key in data) {
    //   if (data.hasOwnProperty(key) && key == "response") {
    //     $("#simulator-debug-content").html(prettyJSON(data[key]));
    //     break;
    //   }
    // }
  }

  function newSessionID() {
    // console.log('new ID')
    return new Date().getTime();
  }

  function isUrlValid(url) {
    // Thanks to https://www.tutorialspoint.com/How-to-validate-URL-address-in-JavaScript
    const pattern = new RegExp(
      "^(https?:\\/\\/)?" + // protocol
      "((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.?)+[a-z]{2,}|" + // domain name
      "((\\d{1,3}\\.){3}\\d{1,3}))" + // ip (v4) address
      "(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*" + //port
      "(\\?[;&amp;a-z\\d%_.~+=-]*)?" + // query string
      "(\\#[-a-z\\d_]*)?$",
      "i"
    );

    return pattern.test(url);
  }

  function updateWatch(previousMinutes, previousDate) {
    const now = new Date();
    const minutes = now.getMinutes();

    if (previousMinutes !== minutes) {
      let min = minutes.toString();
      min = min.length < 2 ? "0" + min : min;
      let hour = now.getHours().toString();
      hour = hour.length < 2 ? "0" + hour : hour;
      $("#phone-watch").html(hour + ":" + min);
    }

    const day = now.getDay();
    const date = now.getDate();

    if (previousDate !== date) {
      const monthNames = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
      ];
      const dayNames = [
        "Sunday",
        "Monday",
        "Tuesday",
        "Wednesday",
        "Thursday",
        "Friday",
        "Saturday",
      ];

      let dayName = dayNames[day];
      let monthName = monthNames[now.getMonth()];
      let year = now.getFullYear();

      $("#phone-date").html(`${dayName} ${date} ${monthName} ${year}`);
    }

    setTimeout(() => {
      updateWatch(minutes, date);
    }, 1000);

    return;
  }

  function prettyJSON(value, sep = "<br>") {
    try {
      let val = JSON.parse(value);
      return JSON.stringify(val, null, sep);
    } catch (e) {
      return value;
    }
  }

  function getValidJson(json) {
    try {
      return JSON.parse(json);
    } catch (error) {
      return false
    }
  }

  function isValidPhoneNumber(number) {
    const SHORTEST_PHONE_LENGTH = 7;
    const LONGEST_PHONE_LENGTH = 15;

    const num = `${number}`;
    return num.length >= SHORTEST_PHONE_LENGTH && num.length <= LONGEST_PHONE_LENGTH
  }

  function handleCustomPhoneNumberChange(event) {
    let number = parsePhoneNumber(event.target.value, false);

    if (!number) {
      event.target.value = "+";
    } else if (!number.startsWith("+")) {
      number = `+${number}`;
    }

    event.target.value = number = number.trim();
    save("msisdn", number);

    $("#retrieved-phone-number").val(number.slice(1))
    // console.log(number.slice(1))
    const network = detectNetwork(number);

    if (network) {
      $('#network').val(network);
    }

    handleNumberAndNetworkMismatch(network)
    handleUnknownNetwork(network, event)
  }

  // To be seriously reviewed
  function handleUnknownNetwork(network, event) {
    if (network) {
      $('#retrieved-networks').val(network);
      $('.unknown-network-error').hide(250);
    } else if (event.type === "change" || event.type === "input") {
      $('.unknown-network-error').show(250);

      setTimeout(() => {
        $('.unknown-network-error').hide(250);
      }, 15000);
    }
  }

  // To be seriously reviewed
  function handleNumberAndNetworkMismatch(network) {
    network = network || detectNetwork($("msisdn").val());
    if (network != $('#network').val()) {
      $('.number-network-mismatch').show(250);

      setTimeout(() => {
        $('.number-network-mismatch').hide(250);
      }, 15000);
    } else {
      $('.number-network-mismatch').hide(250);
    }
  }

  function detectNetwork(number) {
    for (const networkName in networks) {
      if (networks.hasOwnProperty(networkName)) {
        const networkData = networks[networkName];
        const patterns = networkData["patterns"];

        for (const pattern of patterns) {
          const regex = new RegExp(pattern)
          if (regex.test(number)) {
            return networkData['mnc']
          }
        }
      }
    }

    return false;
  }

  function parsePhoneNumber(number, removeAllExtra = true) {
    number = `${number}`.replace(/[^0-9+ -)(]/ig, "");
    // number = number.replace(/\([0-9]\)/g, "");
    number = removeAllExtra ? number.replace(/[ -]/g, "") : number;
    return number;
  }

  function handleRetrievedPhoneNumberChange(event) {
    $("#msisdn").val(this.value)
    $("#retrieved-networks").val(this.selectedOptions[0].dataset.mnc)
    $("#network").val(this.selectedOptions[0].dataset.mnc)
    // console.log(this.selectedOptions[0].dataset.mnc)
  }

  function handleEndpointChange(event) {
    if (this.selectedIndex > 0) {
      $('#endpoint').val(this.value)
      const index = this.selectedIndex - 1;
      const code = this.selectedOptions[index].dataset.code || '';
      $("#ussdCode").val(code);
    }
  }

  function handleEndpointInputChange(event) {
    $("#retrieved-endpoints").val($(this).val())
    if ($("#retrieved-endpoints")[0].selectedIndex > 0) {
      // Because the first one is disabled. The selected indexes will not follow the indexes in the selectedOptions
      const index = $("#retrieved-endpoints")[0].selectedIndex - 1;
      const code = $("#retrieved-endpoints")[0].selectedOptions[index].dataset.code || '';
      // console.log(code)
      // if (code) {
      $("#ussdCode").val(code);
      // }
    } else {
      $("#ussdCode").val('');
    }
  }

  function ussdCodeInputEvent(event) {
    // I don't know what I was supposed to do here
  }

  function handleCustomNetworkChange(event) {
    const network = event.target.value
    $("#retrieved-networks").val(network)
    handleNumberAndNetworkMismatch()
    handleUnknownNetwork(network, event)
  }

  function handleRetrievedNetworkChange(event) {
    const network = event.target.value
    $("#network").val(network)
    handleNumberAndNetworkMismatch()
    handleUnknownNetwork(network, event)
  }
});