Feature: Create short link
  In order share long URL link
  As a client
  I need to create short reference to this link

  @cleanDB
  Scenario: Create short link reference
    Given the "Content-Type" request header contains "application/json"
    And the request body is:
    """
    {
      "name": "google",
      "url": "http://www.google.com"
    }
    """
    When I request "/shortlink" using HTTP "POST"
    Then the response code is 201
    And the response body contains JSON:
    """
    {
      "status": "success"
    }
    """
    And the reference count is 1
