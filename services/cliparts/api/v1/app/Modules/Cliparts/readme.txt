/*
 *---------------------------------------------------------------
 * Save Single Clipart
 *---------------------------------------------------------------
 */
Method: POST

Endpoint: api/v1/cliparts

Parameters: 
    name:Test Clipart
    price: 78.00
    width: 2.00
    height: 2.00
    color_used: 0
    is_scaling: 1
    user_id:1
    categories: [119,393,394,411,121,380,117,115,409,410,112,109,110,414,107,407,413,415,418,420]
    tags:tag789,tag989
    upload: (binary)

Response:
    {"status":1,"message":"Clipart saved successfully","clipart_id":438}

/*
 *---------------------------------------------------------------
 * Update a Single Clipart
 *---------------------------------------------------------------
 */
Method: Put

Endpoint: api/v1/cliparts/<clipart_id>

Parameters: 
    Same as Save

Response:
    {"status":1,"message":"Clipart updated successfully"}


/*
 *---------------------------------------------------------------
 * Delete Bulk Cliparts
 *---------------------------------------------------------------
 */
Method: Delete

Endpoint: /api/v1/cliparts/[1269,1270]

Parameters: 
    None

Response:
    {"status":1,"message":"Clipart deleted successfully"}

/*
 *---------------------------------------------------------------
 * Filters and Sorting of Cliparts
 *---------------------------------------------------------------
 */
 Method: Get

Endpoint: /api/v1/cliparts?name=art&category=[1269,1270]

Parameters: 
    None

Response:
    {"status":1,"message":"Clipart deleted successfully"}