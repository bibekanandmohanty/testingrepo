/*
|--------------------------------------------------------------------------
| Product Decoration Settings
|--------------------------------------------------------------------------
 */

Save Decoration Data 
Post: http://inkxe-v10.inkxe.io/dev/xetool/api/v1/predecorators
'data':
{
  "product_id": 2800,
  "ref_id": 2,
  "name": "A Predecorator Product",
  "price": 1.89,
  "quantity": 56,
  "sku": "ITSMYSKU2019",
  "description": "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s",
  "short_description": "Lorem Ipsum is simply dummy text of the printing and typesetting industry.",
  "categories": [
    {
      "category_id": 3,
      "parent_id": 2
    },
    {
      "category_id": 4,
      "parent_id": 2
    },
    {
      "category_id": 5,
      "parent_id": 2
    }
  ],
  "is_redesign": 1,
  "product_image_url": [
    "https://previews.123rf.com/images/aquir/aquir1311/aquir131100316/23569861-sample-grunge-red-round-stamp.jpg",
    "https://previews.123rf.com/images/aquir/aquir1311/aquir131100316/23569861-sample-grunge-red-round-stamp.jpg"
  ],
  "attributes": [
    {
      "attribute_id": 2,
      "attribute_options": [
        2,
        3,
        4
      ]
    },
    {
      "attribute_id": 2,
      "attribute_options": [
        2,
        3,
        4,
        5
      ]
    }
  ]
}
//End 

/*
|--------------------------------------------------------------------------
| Save Product Variations 
|--------------------------------------------------------------------------
 */

Post: http://inkxe-v10.inkxe.io/dev/xetool/api/v1/predecorators/variations
'data' :
{
  "product_id": 2800,
  "price": 1.9,
  "sku": "ITSMYSKU2020",
  "attributes": [
    {
      "attribute_id": 2,
      "attribute_options": [
        2,
        3,
        4
      ]
    },
    {
      "attribute_id": 2,
      "attribute_options": [
        2,
        3,
        4,
        5
      ]
    }
  ]
}
// End 

/*
|--------------------------------------------------------------------------
| Enable or Disable Image Sides
|--------------------------------------------------------------------------
 */

Get: http://inkxe-v10.inkxe.io/xetool/api/v1/image-sides/disable-toggle/33

/*
|--------------------------------------------------------------------------
| Save Image Sides  
|--------------------------------------------------------------------------
 */

Post : http://inkxe-v10.inkxe.io/xetool/api/v1/image-sides

product_sides: 
{
  "name": "Test for Api",
  "is_default": 0,
  "sides": [
    {
      "side_name": "A1",
      "sort_order": 1,
      "image_upload_data": "product_image_0"
    },
    {
      "side_name": "A2",
      "sort_order": 2,
      "image_upload_data": "product_image_1"
    }
  ]
}

product_image_0 : (binary)
product_image_1 : (binary)

/*
|--------------------------------------------------------------------------
| Update Image Sides
|--------------------------------------------------------------------------
 */

Put : http://inkxe-v10.inkxe.io/xetool/api/v1/image-sides/38

product_sides: 
{
  "name": "Test for Api Edited",
  "is_default": 0,
  "sides": [
    {
      "xe_id": 57,
      "side_name": "A1",
      "sort_order": 1,
      "image_upload_data": "202001081026427246.jpg",
      "is_trash": 0
    },
    {
      "xe_id": 58,
      "side_name": "A2",
      "sort_order": 2,
      "image_upload_data": "",
      "is_trash": 1
    },
    {
      "xe_id": 0,
      "side_name": "A3",
      "sort_order": 3,
      "image_upload_data": "product_image_0",
      "is_trash": 0
    }
  ]
}

product_image_0: (binary)

/*
|--------------------------------------------------------------------------
| Fetch Decoration Area
|--------------------------------------------------------------------------
 */
{
  "product_id": 148,
  "is_crop_mark": 1,
  "is_safe_zone": 1,
  "crop_value": 1,
  "safe_value": 1,
  "is_3d_preview": 1,
  "scale_unit_id": 1,
  "product_image_id": 1,
  "print_profile_ids": [
    5,
    6,
    7
  ],
  "sides": [
    {
      "name": "Front",
      "image_dimension": "Dimension of the image",
      "is_visible": 1,
      "product_image_side_id": 2,
      "product_decoration": [
        {
          "name": "This is a sample data",
          "mask_json_file": "{}",
          "print_area_id": 2,
          "custom_min_height": 23.89,
          "custom_max_height": 23.89,
          "custom_min_width": 23.89,
          "custom_max_width": 23.89,
          "custom_bound_price": 23.89,
          "is_border_enable": 0,
          "is_sides_allow": 0,
          "no_of_sides": 1,
          "is_dimension_enable": 0,
          "print_profile_ids": [
            5,
            6,
            7
          ]
        },
        {
          "name": "This is a sample data",
          "mask_json_file": "{}",
          "print_area_id": 2,
          "custom_min_height": 23.89,
          "custom_max_height": 23.89,
          "custom_min_width": 23.89,
          "custom_max_width": 23.89,
          "custom_bound_price": 23.89,
          "is_border_enable": 0,
          "is_sides_allow": 0,
          "no_of_sides": 1,
          "is_dimension_enable": 0,
          "print_profile_ids": [
            5,
            6,
            7
          ]
        }
      ]
    },
    {
      "name": "Back",
      "image_dimension": "Dimension of the image",
      "is_visible": 1,
      "product_image_side_id": 5,
      "product_decoration": [
        {
          "name": "This is a sample data",
          "mask_json_file": "{}",
          "print_area_id": 2,
          "custom_min_height": 23.89,
          "custom_max_height": 23.89,
          "custom_min_width": 23.89,
          "custom_max_width": 23.89,
          "custom_bound_price": 23.89,
          "is_border_enable": 0,
          "is_sides_allow": 0,
          "no_of_sides": 1,
          "is_dimension_enable": 0,
          "print_profile_ids": [
            5,
            6,
            7
          ]
        },
        {
          "name": "This is a sample data",
          "mask_json_file": "{}",
          "print_area_id": 2,
          "custom_min_height": 23.89,
          "custom_max_height": 23.89,
          "custom_min_width": 23.89,
          "custom_max_width": 23.89,
          "custom_bound_price": 23.89,
          "is_border_enable": 0,
          "is_sides_allow": 0,
          "no_of_sides": 1,
          "is_dimension_enable": 0,
          "print_profile_ids": [
            5,
            6,
            7
          ]
        }
      ]
    }
  ]
}

{
	status: 1,
	data: {
		print_profile: [{
				id: 77,
				name: "Screen print"
			},
			{
				id: 144,
				name: "DTG printing"
			}
		],
		attributes: [{
				id: 1,
				name: "Color",
				attribute_term: [{
						id: 40,
						name: "Black",
						hex_code: "#7c8bb7",
						file_name: "",
						price_data: [{
								print_profile_id: 77,
								price: 0
							}
						]
					},
					{
						id: 45,
						name: "Brown",
						hex_code: "#cccccc",
						file_name: "",
						price_data: [{
								print_profile_id: 77,
								price: 0
							}
						]
					}
				]
			},
			{
				id: 2,
				name: "Size",
				attribute_term: [{
						id: 68,
						name: "Large",
						price_data: [{
								print_profile_id: 77,
								price: 0
							}
						]
					},
					{
						id: 74,
						name: "Medium",
						price_data: [{
								print_profile_id: 77,
								price: 0
							}
						]
					}
				]
			}
		]
	}
}