# VdoCipher Server-Side API: Installation and Integration Guide

This guide provides backend developers (Codex) with a comprehensive overview of installing and integrating the VdoCipher server-side API for secure video playback and management.

## 1. Overview

The VdoCipher Server API provides RESTful APIs for video asset management, enabling programmatic pipelines to automate business operations related to video actions.[1] It covers playback authentication, video management, and automatic uploads. This guide focuses on API Version v3.[1]

## 2. Authorization

All API requests to VdoCipher require authentication using your API keys.[2]

### Client Secret Key Generation and Usage:

1.  **Obtain API Key**: Your API key (Client Secret) is provided by VdoCipher. Ensure it is kept private.[2]
2.  **Authorization Header**: Include the API key in the `Authorization` header of your HTTP requests. The header value must be prefixed with `Apisecret` followed by a space.[2]

    **Example Header**:
    `Authorization: Apisecret a1b2c3d4e5`
[2]
## 3. Playback Authentication (OTP)

VdoCipher uses One-Time Passwords (OTPs) to authorize video playback securely. OTPs must always be generated on your backend server and then sent to the frontend for use in the video embed code.[3]

### Core Workflow:

1.  Your backend server makes an HTTP POST request to the VdoCipher API to generate an OTP.[3]
2.  The VdoCipher API returns a JSON object containing the `otp` and `playbackInfo`.[3]
3.  Your backend sends both `otp` and `playbackInfo` to your website frontend.[3]
4.  The frontend uses these values as part of the video embed code to play the video.[3]

### API Endpoint:

The API endpoint for retrieving an OTP is:
`https://dev.vdocipher.com/api/videos/{videoID}/otp`
[3]
Replace `{videoID}` with the ID of the video you want to play.[3]

### Required Headers:

*   `Accept: application/json`
[3]*   `Content-Type: application/json`
[3]*   `Authorization: Apisecret YOUR_API_SECRET_KEY`
[3]
### Request Body Parameters:

The POST request body is a JSON object that can include various optional parameters to configure the OTP's behavior.[4][3]

**Example Request Body (minimal)**:
```json
{
  "ttl": 300
}
```
This example sets the Time-to-Live (TTL) for the OTP to 300 seconds (5 minutes).[3]

### Successful Response:

A successful request returns a JSON object containing the `otp` and `playbackInfo`:[3]
```json
{
  "otp": "1234567890abcdefghijk",
  "playbackInfo": "z1y2x3w4v5u6t7s8r9q10"
}
```

## 4. OTP Configuration Options

You can customize OTP behavior by including additional parameters in the request body.[4]

### `ttl` (Time To Live):[5]

*   **Purpose**: Sets the validity duration of the OTP.
*   **Default**: 6 hours.
*   **Value**: Integer, in seconds.
*   **Recommendation**: VdoCipher recommends a minimum TTL of 5 minutes (300 seconds) to account for slow connections.[5] A very long TTL (e.g., 30 years) can create static video URLs for non-premium content.[5]
*   **Example**:
    ```json
    {
      "ttl": 300
    }
    ```

### `annotate` (Watermarking):[6]

*   **Purpose**: Adds dynamic watermarks (annotations) to the video.
*   **Value**: A JSON string representing an array of annotation objects. This parameter requires extra serialization (e.g., `JSON.stringify()` in JavaScript, `json_encode()` in PHP).[6]
*   **Example (dynamic text watermark)**:
    ```json
    {
      "annotate": "[{'type':'rtext', 'text':' {name}', 'alpha':'0.60', 'color':'0xFF0000','size':'15','interval':'5000'}]"
    }
    ```
    Note the inner JSON string needs to be properly escaped or serialized.[6]

### `whitelisthref` (Domain/IP Restrictions):[7]

*   **Purpose**: Restricts video playback to specific domains or IP addresses.
*   **Value**: A string that matches a regex. If the hostname does not contain the specified string, playback is restricted.[7]
*   **Example**:
    ```json
    {
      "whitelisthref": "vdocipher.com"
    }
    ```

### `ipGeo` (Geo-blocking):[8]

*   **Purpose**: Restricts or whitelists countries or IP addresses for video playback, useful for licensing or security reasons.[8]
*   **Value**: An object containing `allow`, `block`, or `except` lists. Each list is an array of strings (two-letter ISO 3166-1 alpha2 country codes or IPv4 addresses).[8] IPv6 support is available upon request.[8]
*   **Recommendation**: Restrict the OTP to the user's IP address.[8]
*   **Example (allow specific IP)**:
    ```json
    {
      "ipGeo": {"allow": ["123.123.123.123"]}
    }
    ```
*   **Example (block set of countries except one IP)**:
    ```json
    {
      "ipGeo": {"block": ["US", "CA"], "except": ["123.123.123.123"]}
    }
    ```

### `licenseRules` (Offline Download Permissions):[9]

*   **Purpose**: Allows viewers to download an encrypted version of the video to their Android/iOS devices via SDKs, with optional rental duration.
*   **Value**: A JSON string containing `canPersist` (boolean) and `rentalDuration` (integer, in seconds).
*   **Example (allow persistence for 15 days)**:
    ```json
    {
      "licenseRules": "{\"canPersist\": true, \"rentalDuration\": 1296000}"
    }
    ```
    (1296000 seconds = 15 days)
[9]
### `userId` (Viewer Identification for Analytics):[10]

*   **Purpose**: Associates playback with a specific user for viewer-based analytics (e.g., identifying password sharing, tracking usage, understanding watch patterns).[10]
*   **Value**: String, representing a unique user identifier from your database.
*   **Constraints**: Max 36 characters, alphanumeric, hyphens (`-`), and underscores (`_`) allowed. Do not use identifiable information like email or phone numbers.[10] Avoid static values like "unknown" or "guest".[10]
*   **Example**:
    ```json
    {
      "userId": "a12v-b34s-cef3"
    }
    ```

## 5. Webhooks

VdoCipher webhooks allow your server to programmatically listen to video status change events, such as `video:ready` or `video:readyall`.[11]

### Webhook Actions:

You can perform the following actions with webhooks:[11]

*   List all hooks
*   Create new hook
*   Delete a hook

### Webhook HTTP Request Information:

*   **Method**: `POST`
[11]*   **Content-type**: `application/json`
[11]*   **Supported Event Types**: `http`
[11]*   **Supported Events**: `video:ready`, `video:readyall`
[11]
### Sample Webhook Body (received by your server):

```json
{
  "hookId": "5d56a5234f99xxxxxxxxxxxxxxxxxxxx",
  "event": "video:ready",
  "time": 1538330269833,
  "payload": {
    "id": "8cf4129200a4xxxxxxxxxxxxxxxxxxxx",
    "title": "The Daily Dweebs.mp4",
    "upload_time": 1538329973,
    "tags": null,
    "length": 60,
    "status": "ready"
  }
}
```
This body is sent to the `value` URL you specify when creating the hook.[11]

### API Endpoints and Sample CURL Statements:

All webhook API calls require the `Authorization` header as described in Section 2.[11]

1.  **List all hooks**:
    ```bash
    curl -i https://dev.vdocipher.com/api/hooks/ \
    -H 'authorization: Apisecret YOUR_API_SECRET_KEY' \
    -H 'content-type: application/json'
    ```
[11]
2.  **Create new hook**:
    ```bash
    curl -i -X POST https://dev.vdocipher.com/api/hooks/ \
    -H 'authorization: Apisecret YOUR_API_SECRET_KEY' \
    -H 'content-type: application/json' \
    -d '{"type": "http", "value": "http://example.com/path/","event": "video:ready", "status": 1}'
    ```
[11]    *   `type`: Must be `http`.[11]
    *   `value`: The URL where VdoCipher will send POST requests for the specified event.[11]
    *   `event`: The event to subscribe to (e.g., `video:ready`).[11]
    *   `status`: `1` to enable the hook.[11]

3.  **Delete a hook**:
    ```bash
    curl -i -X DELETE https://dev.vdocipher.com/api/hooks/ \
    -H 'authorization: Apisecret YOUR_API_SECRET_KEY' \
    -H 'content-type: application/json' \
    -d '{"uuid": "YOUR_HOOK_UUID"}'
    ```
[11]    *   `uuid`: The unique identifier of the hook to delete.[11]

---

Sources:
[1] VdoCipher API Overview | Automate Video Asset Mangement ... (https://www.vdocipher.com/docs/server)
[2] Authorizing the API | VdoCipher Documentation (https://www.vdocipher.com/docs/server/authorization)
[3] Obtaining OTP using API | VdoCipher Documentation (https://www.vdocipher.com/docs/server/playbackauth/otp)
[4] OTP Request Body | VdoCipher Documentation (https://www.vdocipher.com/docs/server/playbackauth/reqbody)
[5] Time-to-live for OTP Validity | VdoCipher Documentation (https://www.vdocipher.com/docs/server/playbackauth/ttl)
[6] Generating Annotation Code/ Watermark | VdoCipher Documentation (https://www.vdocipher.com/docs/server/playbackauth/anno)
[7] URL Whitelist | VdoCipher Documentation (https://www.vdocipher.com/docs/server/playbackauth/whitelist)
[8] IP and Geo Rules | VdoCipher Documentation (https://www.vdocipher.com/docs/server/playbackauth/ipgeo)
[9] Generating OTP for offline use | VdoCipher Documentation (https://www.vdocipher.com/docs/server/playbackauth/offline)
[10] Viewer Based Analytics | VdoCipher Documentation (https://www.vdocipher.com/docs/server/playbackauth/viewer)
[11] Web Hooks | VdoCipher Documentation (https://www.vdocipher.com/docs/server/account/hooks)
