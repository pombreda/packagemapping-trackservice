/*
 * Copright 2014 Zetta Code
 * 
 * Code example for communicating with the PackageMapping Tracking Web Service API
 * 
 * http://commercial.packagemapping.com
 * 
 */
using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net;
using Newtonsoft.Json;

namespace ZettaCode.Web.Services.Tracking.Client.Examples
{
    public partial class JsonExample : System.Web.UI.Page
    {
        public class GetTrackListRequest
        {
            public string AllowedCarrierCodes { get; set; }
            public List<SearchParameter> SearchParameters { get; set; }
            public string WebServiceKey { get; set; }

            public GetTrackListRequest()
            {
                SearchParameters = new List<SearchParameter>();
            }
        }

        public class SearchParameter
        {
            public string CarrierCode { get; set; }
            public string TrackingNumber { get; set; }
        }

        protected void Page_Load(object sender, EventArgs e)
        {
            Output.Text = "<br/>";

            try
            {
                /* Instantiate a new request */
                GetTrackListRequest getTrackListRequest = new GetTrackListRequest();
                
                /* Enter your Web Service Key here */
                getTrackListRequest.WebServiceKey = "";

                /* Add up to 50 SearchParameters */
                getTrackListRequest.SearchParameters.Add(new SearchParameter()
                {
                    /* Enter the CarrierCode for more accurate results or use "auto" */
                    /* See documentation for a list of CarrierCodes */
                    CarrierCode = "",
                    /* Enter the tracking number */
                    TrackingNumber = ""
                });

                /* Serialize the JSON object */
                var jsondata = JsonConvert.SerializeObject(getTrackListRequest);

                /* POST the request to the web service */
                var url = "https://ws.packagemapping.info/Services/PackageMapping/ITrackService/rest/json/GetTrackList";
                var httpWebRequest = (HttpWebRequest)WebRequest.Create(url);
                httpWebRequest.ContentType = "application/json; charset=utf-8";
                httpWebRequest.Accept = "application/json";
                httpWebRequest.Method = "POST";

                /* Send the request data */
                using (var streamWriter = new StreamWriter(httpWebRequest.GetRequestStream()))
                {
                    string json = jsondata;
                    streamWriter.Write(json);
                }

                /* Get the response data */
                var httpResponse = (HttpWebResponse)httpWebRequest.GetResponse();

                /* Populate the dynamic JSON object with the response */
                dynamic getTrackListResponse = null;
                using (var streamReader = new StreamReader(httpResponse.GetResponseStream()))
                {
                    string responseText = streamReader.ReadToEnd();
                    getTrackListResponse = JsonConvert.DeserializeObject(responseText);
                    
                    /* If you want to view the JSON response, uncomment below */
                    //Output.Text = responseText + "<br/>";
                }

                /* Check if the transmission was successfull */
                if ((bool)getTrackListResponse.Success)
                {
                    /* Loop through the Tracks */
                    foreach (dynamic trackInformation in getTrackListResponse.Tracks)
                    {
                        /* Check if the Track was successful */
                        if ((bool)trackInformation.Success)
                        {
                            /* Get summary information */
                            string origin = trackInformation.Origin;
                            string destination = trackInformation.Destination;
                            string scheduledDeliveryDateTime = trackInformation.ScheduledDeliveryDateTime;
                            string addressRerouteToDestination = trackInformation.AddressRerouteToDestination;
                            string resultsLastUpdated = trackInformation.ResultsLastUpdatedUtcDateTime;

                            if (!string.IsNullOrEmpty(scheduledDeliveryDateTime))
                            {
                                Output.Text += "Estimated Delivery: " + scheduledDeliveryDateTime + "<br/>";
                            }
                            Output.Text += "Last Updated: " + resultsLastUpdated + " (UTC)<br/>";

                            /* Get latest status information */
                            if (trackInformation.LatestStatus != null)
                            {
                                string latestStatus = trackInformation.LatestStatus.Status;
                                string latestStatusMessage = trackInformation.LatestStatus.StatusMessage;
                                string latestStatusLocation = trackInformation.LatestStatus.Location;
                                Output.Text += "Latest Status: " 
                                    + latestStatus + ": " 
                                    + latestStatusMessage + " - " 
                                    + latestStatusLocation 
                                    + "<br/>";
                            }

                            /* Loop throught the activity scans */
                            Output.Text += "Activities<br/>";
                            foreach (dynamic activity in trackInformation.Activities)
                            {
                                string activityDate = activity.Date;
                                string activityTime = activity.Time;
                                string activityStatus = activity.Status;
                                string activityStatusMessage = activity.StatusMessage;
                                string activityLocation = activity.Location;

                                Output.Text += activityDate + " " 
                                    + activityTime + ": " 
                                    + activityStatus + ": " 
                                    + activityStatusMessage + ": " 
                                    + activityLocation 
                                    + "<br/>";
                            }
                        }
                        else
                        {
                            Output.Text += "No match or no tracking information yet.";
                        }
                    }
                }
                else
                {
                    Output.Text += "Tracking service is temporarily unavailable.<br/>";

                    /* For developers only. Don't display to client. */
                    //Output.Text += "Error: " + getTrackListResponse.FailureInformation;

                    /* If you get here and FailureInformation is blank, it most likely means the 
                     * Web Service Key you entered is invalid.  No error information is returned for 
                     * invalid Web Service Keys for security reasons. 
                     */
                }
            }
            catch (Exception ex)
            {
                Output.Text += "Tracking service is temporarily unavailable.<br/>";

                /* For developers only. Don't display to client. */
                //Output.Text += ex.Message;

                /* If you get a 400 Bad Request, please make sure you are sending your request properly.
                 * For instance, make sure you set a valid value for the Web Service Key.
                 * No error information is returned for invalid Web Service Keys for security reasons.
                 */
            }

        }
    }
}