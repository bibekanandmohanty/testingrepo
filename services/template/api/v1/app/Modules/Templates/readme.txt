Total Database Tables Used

- design_states
- capture_images
- templates
- template_tags
- template_tag_rel
- categories (common table)
- template_category_rel
- template_print_profile_rel

Developers worked
- Tanmaya

Description
- 

Routes List with parameters: 

9. capture Images 
- POST 
- URL : - URL : <api_root_url>/api/v1/capture-images
- Params 
    "template[]": "image_0",
    "template[]": "image_1",
    "image_0":(binary),
    "image_1":(binary),

    "template_with_product[]": "image_0",
    "template_with_product[]": "image_1",
    "image_0":(binary),
    "image_1":(binary),
- Response 
{
    "status": 1,
    "data": {
        "template_file_url": [
            {
                "file_name": "202001060221031926.jpg",
                "url": "http://inkxe-v10.inkxe.io/dev/xetool/assets/temp/202001060221031926.jpg",
                "thumbnail": "http://inkxe-v10.inkxe.io/dev/xetool/assets/temp/thumb_202001060221031926.jpg"
            },
            {
                "file_name": "202001060221038884.jpg",
                "url": "http://inkxe-v10.inkxe.io/dev/xetool/assets/temp/202001060221038884.jpg",
                "thumbnail": "http://inkxe-v10.inkxe.io/dev/xetool/assets/temp/thumb_202001060221038884.jpg"
            },
            {
                "file_name": "202001060221037858.jpg",
                "url": "http://inkxe-v10.inkxe.io/dev/xetool/assets/temp/202001060221037858.jpg",
                "thumbnail": "http://inkxe-v10.inkxe.io/dev/xetool/assets/temp/thumb_202001060221037858.jpg"
            }
        ]
    }
}

1. get all templates 
- GET 
- URL : <api_root_url>/api/v1/templates 
- Params : None 

2. get a single Template file 
- GET 
- URL : <api_root_url>/api/v1/templates/<template_id>
- Params : None 

3. save a template 
- POST 
- URL : <api_root_url>/api/v1/templates
- Params: 
    'name' : This is a test Tempalte Edited
    'product_variant_id' : 2
    'product_settings_id' : 1
    'custome_price' : 12
    'no_of_colors' : 2
    'template_type' : template
    'print_profiles' : [3,4,5]
    'color_codes' : ["#ododod", "#abababa"]
    'description' : The Description Edited
    'tags' : ["BBSR","CTC","RKL"]
    'categories' : [1,2,3]
    'design_data' : [{"side":1,"svg":"<url_encoded_svg_data>"},{"side":2,"svg":"<url_encoded_svg_data>"}]
    'captured_images' : {"template_file_url":[{"file_name":"202001060221031926.jpg","url":"http://3.18.103.242/xetool/assets/temp/201912180703347309.png"},{"file_name":"202001060221038884.jpg","url":"http://3.18.103.242/xetool/assets/temp/201912180703347309.png"},{"file_name":"202001060221037858.jpg","url":"http://3.18.103.242/xetool/assets/temp/201912180703347309.png"}],"template_with_product_file":[]}

4. update a template 
- PUT 
- URL : <api_root_url>/api/v1/templates/<ref_id>
- Params: 
    'name' : This is a test Tempalte Edited
    'product_variant_id' : 2
    'product_settings_id' : 1
    'custome_price' : 12
    'no_of_colors' : 2
    'template_type' : template
    'print_profiles' : [3,4,5]
    'color_codes' : ["#ododod", "#abababa"]
    'description' : The Description Edited
    'tags' : ["BBSR","CTC","RKL"]
    'categories' : [1,2,3]

5. delete a template 
- DELETE 
- URL : <api_root_url>/api/v1/templates/<template_id>

6. get all categories 
- GET 
- URL : <api_root_url>/api/v1/templates/categories 
- Response: 
{
    "status": 1,
    "data": [
        {
            "id": 40,
            "name": "weather",
            "order": null,
            "is_disable": 0,
            "is_default": 0,
            "subs": [
                {
                    "id": 41,
                    "name": "hunting",
                    "order": null,
                    "is_disable": 0,
                    "parent_id": 40,
                    "is_default": 0,
                    "subs": []
                }
            ]
        }
    ]
}

7. save a category 
- POST 
- URL : <api_root_url>/api/v1/templates/categories
- Params : 
 name : "asdsad"
 parent_id : 2 

8. save a category 
- PUT 
- URL : <api_root_url>/api/v1/templates/categories/2
- Params : 
 name : "asdsad"
 parent_id : 2

10. delete a category 
- DELETE 
- URL : <api_root_url>/api/v1/templates/categories/2
- Params : None