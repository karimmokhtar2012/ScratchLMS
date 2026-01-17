# Groq Reasoning API Report

## Overview
This report details the API response schema and usage patterns for Groq's OpenAI-compatible "reasoning" endpoint, specifically focusing on handling chat completions and structured outputs.

## API Response Schema

Groq's Chat Completions API is compatible with OpenAI's response format. The core response object contains:

*   **`id`**: A unique identifier for the request.
*   **`object`**: The object type, which is always `chat.completion`.
*   **`created`**: The Unix timestamp (in seconds) of when the completion was created.
*   **`model`**: The model used for the completion.
*   **`choices`**: A list of chat completion choices. Each choice contains:
    *   **`index`**: The index of the choice in the list.
    *   **`message`**: A chat message object with:
        *   **`role`**: The role of the author of this message (e.g., `assistant`).
        *   **`content`**: The contents of the message (the actual text response).
    *   **`finish_reason`**: The reason the model stopped generating tokens (e.g., `stop`, `length`).

### Structured Outputs (JSON)

When utilizing Groq's "Structured Outputs" feature (typically by setting `response_format: { "type": "json_object" }` or enforcing a JSON schema):

*   The **`content`** field within the `message` object will contain a string that is valid JSON.
*   **Strict Mode (`strict: true`)**: Guarantees the output matches the provided JSON schema exactly.
*   **Best-effort Mode (`strict: false`)**: Attempts to match the schema but may occasionally deviate.

## Extracting Model Output

### Text Extraction
To retrieve the plain text response:
```javascript
const text = response.choices[0].message.content;
```

### JSON Extraction
To reliably extract a JSON payload (e.g., for score/feedback):

1.  **Enforce JSON Mode**: Ensure the API request includes `response_format: { "type": "json_object" }` and the system prompt instructs the model to output JSON.
2.  **Parse the Content**:
    ```javascript
    try {
        const content = response.choices[0].message.content;
        const jsonPayload = JSON.parse(content);
        const score = jsonPayload.score;
        const feedback = jsonPayload.feedback;
    } catch (error) {
        console.error("Failed to parse JSON response:", error);
    }
    ```

## Usage Details

### Authentication
*   **Header**: `Authorization: Bearer $GROQ_API_KEY`

### Required Parameters
*   **`model`**: e.g., `llama3-70b-8192` or `mixtral-8x7b-32768`.
*   **`messages`**: An array of message objects (roles: `system`, `user`).

### Rate Limits & Error Handling
*   **Rate Limits**: Headers like `x-ratelimit-limit-requests` and `x-ratelimit-remaining-requests` are typically returned.
*   **Errors**: Standard HTTP status codes apply (e.g., 401 Unauthorized, 429 Too Many Requests, 500 Internal Server Error). The response body for errors usually follows:
    ```json
    {
      "error": {
        "message": "Error description",
        "type": "error_type",
        "param": null,
        "code": "error_code"
      }
    }
    ```

## Example Response

A successful response with a JSON payload for grading might look like this:

```json
{
  "id": "chatcmpl-123",
  "object": "chat.completion",
  "created": 1677652288,
  "model": "llama3-70b-8192",
  "choices": [{
    "index": 0,
    "message": {
      "role": "assistant",
      "content": "{\n  \"score\": 85,\n  \"feedback\": \"Good understanding of the core concepts, but missed a few edge cases.\"\n}"
    },
    "finish_reason": "stop"
  }],
  "usage": {
    "prompt_tokens": 50,
    "completion_tokens": 30,
    "total_tokens": 80
  }
}
```
