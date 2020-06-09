// window.$ = window.jQuery = require("./js/jquery-3.1.0.min.js");
$(document).ready(function () {
  const APP_REQUEST_INIT = "1";
  const APP_REQUEST_END = "17";
  const APP_REQUEST_FAILED = "3";
  const APP_REQUEST_CANCELLED = "30";
  const APP_REQUEST_ASK_USER_RESPONSE = "2";
  const APP_REQUEST_USER_SENT_RESPONSE = "18";
  const SIMULATOR_USSD_API_ENDPOINT = "api/v1/ussd.php"

  let shortcode = retrieve("shortcode", "*380*75#");
  let currentRequest = null;
  let sessionID = null;

  if (!$("#endpoint").val()) {
    $("#endpoint").val(retrieve("endpoint", "http://"))
  }

  if (!$("#shortcode").val()) {
    $("#shortcode").val(shortcode)
  }

  $("#dial-shortcode-tooltip").text(shortcode);

  $(".cancel").hide();
  $("form").submit((e) => e.preventDefault());
  $(".send").click(sendRequest);
  $(".cancel").click(cancelRequest);
  $(".toggle-controls").click(toggleControls);

  $("#shortcode").on("input", (e) => {
    shortcode = e.target.value;
    $("#dial-shortcode-tooltip").text(shortcode);
    save("shortcode", shortcode);
  });

  $("#dial-shortcode-tooltip").on("input", (e) => {
    shortcode = e.target.innerText;
    $("#shortcode").val(shortcode);
    save("shortcode", shortcode);
  });

  $("#endpoint").on("change", (e) => {
    if (!e.target.value) {
      e.target.value = "http://";
    }

    save("endpoint", e.target.value);
  });

  /*   const config = { attributes: true, characterData: true };
  const callback = (mutationList, observer) => {
    for (let mutation of mutationList) {
      console.log(mutation);

      if (mutation.type === "attributes") {
        console.log(mutation);
      } else if (mutation.type === "charactetData") {
      }
    }
  };

  const observer = new MutationObserver(callback);
  observer.observe(document.getElementById("shortcode"), config);
  observer.observe(document.getElementById("dial-shortcode-tooltip"), config);
 */
  updateWatch();
  toggleControls();

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
    $("#simulator-response-input").val(shortcode);
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
    $("#simulator-debug-content").html(desc);
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
        sessionID
      },
    };

    let parsed = {};

    if (response.constructor !== Object) {
      try {
        parsed = JSON.parse(response);
      } catch (error) {
        // Application failed
        responseToProcess.data.message =
          "Could not parse the response. <br ><small>The response got from the server is not a valid JSON string.<br>It typically means an error happened at the USSD application side. Kindly read the response (above) to know what was the error.";
        responseToProcess.data.requestType = APP_REQUEST_FAILED;
        console.log(error);
        return responseToProcess;
      }
    }

    if (parsed.INFO) {
      responseToProcess.data.INFO = parsed.INFO;
    }

    if (parsed.WARNING) {
      responseToProcess.data.WARNING = parsed.WARNING;
    }

    if (parsed.message !== undefined && parsed.ussdServiceOp !== undefined) {
      responseToProcess.data.requestType = `${parsed.ussdServiceOp}`;
      responseToProcess.data.message = parsed.message ? `${parsed.message}` : 'No message';

      if (
        responseToProcess.data.requestType !== APP_REQUEST_ASK_USER_RESPONSE &&
        responseToProcess.data.requestType !== APP_REQUEST_END
      ) {
        // Application Failed.2
        responseToProcess.data.message =
          "Got Invalid 'ussdServiceOp' from the application on th server.";
        responseToProcess.data.requestType = APP_REQUEST_FAILED;
      }
    } else {
      responseToProcess.data.requestType = APP_REQUEST_FAILED;
      responseToProcess.data.message =
        "Response parsed successfully but does not contain expected values (message, ussdServiceOp)"; // Application failed with invalid response.
    }

    return responseToProcess;
  }

  function processServerResponse(response) {
    hideLoader();
    $("#loading").html("Request Completed.");

    const responseToProcess = parseResponse(response);

    $.each(responseToProcess, function (key, val) {
      if (key === "response") {
        $("#simulator-debug-content").html(prettyJSON(val));
      }

      if (key === "data") {
        // const message = val.message;
        // const requestType = val.requestType;
        // sessionID = val.sessionID;

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
            const description = responseToProcess.response ?
              responseToProcess.response + "<br><br>" + error :
              error;
            ussdEnd("Application Failed.", description);
            break;
        }

        if (responseToProcess.data.INFO) {
          $("#simulator-info-content").html(
            "<span class='text-info'>INFO:</span><br>" +
            JSON.stringify(responseToProcess.data.INFO)
          );
        }

        if (responseToProcess.data.WARNING) {
          $("#simulator-warning-content").html(
            "<span class='text-danger'>WARNING:</span><br>" +
            JSON.stringify(responseToProcess.data.WARNING)
          );
        }
        /*         $("#ussd-popup-content").text(val.message);
$("#ussd-popup").fadeIn(500);

switch (val.requestType) {
  case APP_REQUEST_ASK_USER_RESPONSE:
    $("#simulator-response-input").focus();
    break;
  case APP_REQUEST_END:
    $("#simulator-response-input").blur();
    break;
} */
      }
    });
  }

  function sendRequest() {
    $("#loading").html("Processing USSD Request...");
    $("#dial-shortcode").hide();
    $("#ussd-popup").fadeOut(50);
    showLoader();
    $(".cancel").html("CANCEL");
    $(".cancel").show();
    $(".send").hide();

    let requestType;
    /*
    if ($("#simulator-response-input").val() != shortcode && sessionID) {
      requestType = APP_REQUEST_USER_SENT_RESPONSE;
    } */

    if (sessionID === null) {
      requestType = APP_REQUEST_INIT;
      sessionID = newSessionID();
      $("#simulator-response-input").val(shortcode);
    } else {
      requestType = APP_REQUEST_USER_SENT_RESPONSE;
    }

    const userResponse = $("#simulator-response-input").val();

    if (!userResponse) {
      ussdEnd("Empty response not allowed.");
      return;
    }

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

    const msisdn = $("#msisdn").val();
    const network = $("#network").val();
    /*
    const url =
    `api/v1/ussd.php?endpoint=${encodeURIComponent(endpoint)}&type=${requestType}&content=${userResponse}&msisdn=${msisdn}&network=${network}&sessionID=${sessionID}`;

    currentRequest = $.getJSON(url, processServerResponse);
    */
    const data = {
      ussdString: encodeURIComponent(userResponse),
      ussdServiceOp: encodeURIComponent(requestType),
      sessionID: encodeURIComponent(sessionID),
      msisdn: encodeURIComponent(msisdn),
      network: encodeURIComponent(network),
      endpoint: encodeURIComponent(endpoint),
    };

    const failCallback = (error) => {
      let display = "";
      let description = "";

      if (error.readyState === 0) {
        display = "Application not available.";
        description = `Cannot reach the endpoint: <span class='text-danger'>${endpoint}</span>
        <br><br>
        <ul>
          <li>Check if the endpoint provided is correct.</li>
          <li>Check if the server hosting the application is running.</li>
          <li>Check if the simulator is running on the same server as your application.</li>
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

    $("#simulator-info-content").html("");
    $("#simulator-warning-content").html("");

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
      $("#dial-shortcode-icon").html("&#x1F4DE;");
      $("#dial-shortcode").fadeIn(500);
      $("#loading").html("Request Cancelled.");
    }, 300);

    /*
    // If no request is in processing but the user click on CANCEL
    // Might not normally happen because the cancel button is hidden when there is no request.
    if (sessionID === null) {
      $("#simulator-debug-content").html('Press "DIAL" to initiate a request.');
      return;
    }
    */

    sessionID = null;

    if (sendCancelRequest) {
      const msisdn = $("#msisdn").val();
      const network = $("#network").val();
      const userResponse = "";
      const requestType = APP_REQUEST_CANCELLED;
      let endpoint = $("#endpoint").val();

      if (!endpoint) {
        $("#simulator-debug-content").text("Missing Endpoint URL");
        return;
      }

      if (!endpoint.endsWith("/")) {
        endpoint += "/";
      }

      if (!isUrlValid(endpoint)) {
        $("#simulator-debug-content").text("Invalid Endpoint URL");
        return;
      }

      const data = {
        ussdString: encodeURIComponent(userResponse),
        ussdServiceOp: encodeURIComponent(requestType),
        sessionID: encodeURIComponent(sessionID),
        msisdn: encodeURIComponent(msisdn),
        network: encodeURIComponent(network),
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
    $("#loading").html("Request Completed.");

    setTimeout(() => {
      $("#ussd-popup-content").text("");
      $("#simulator-response-input-field").hide();
      $("#simulator-response-input").blur();
      $("#simulator-response-input").val(shortcode);
    }, 100);

    for (const key in data) {
      if (data.hasOwnProperty(key) && key == "response") {
        $("#simulator-debug-content").html(prettyJSON(data[key]));
        break;
      }
    }
  }

  function newSessionID() {
    console.log('new ID')
    return new Date().getTime();
  }

  function isUrlValid(url) {
    // Regex got from https://www.tutorialspoint.com/How-to-validate-URL-address-in-JavaScript
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

    // Old Regex
    /*
    return /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(
        url
    );
    */
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

  function prettyJSON(value, sep = "<br />") {
    try {
      let val = JSON.parse(value);
      return JSON.stringify(val, null, sep);
    } catch (e) {
      return value;
    }
  }
});