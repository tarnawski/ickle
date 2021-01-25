Feature: Retrieve short link
  In order check reference URL of short link
  As a client
  I need to get short link reference

  Background:
    Given There are the following references:
      | ID                                   | NAME          | URL                          | CREATED_AT            |
      | c3b18f0a-e5ca-4868-8e40-0d63fafbe305 | abe0ba94f106  | http://zbgmzmhzh.mfrwbv.biz  | 2019-06-17 18:24:21   |
      | 7dc161be-dc54-4ff6-96e6-88e088db6b56 | ba94a94fbebe  | http://wbmfrwbv.gmzm.biz     | 2021-01-20 16:13:39   |

  @cleanDB
  Scenario: Get short link reference
    Given the "Content-Type" request header contains "application/json"
    When I request "/shortlink/abe0ba94f106"
    Then the response code is 200
    And the response body contains JSON:
    """
    {
      "shortlink": "http://zbgmzmhzh.mfrwbv.biz"
    }
    """
