Print Area have 2 Types of Records
- System Defined
- User Defined 

System Defined Records can not be deleted if request come from Front end.

Fetching and Sorting Endpoints
--------------------------------------
<api_base_url>/v1/print-areas?sortby=height&order=desc
<api_base_url>/v1/print-areas?sortby=width&order=asc
<api_base_url>/v1/print-areas?sortby=name&order=asc

/*
|--------------------------------------------------------------------------
| Create Fixed Decoration Area
|--------------------------------------------------------------------------
 */
POST : http://inkxe-v10.inkxe.io/xetool/api/v1/decorations

'decorations': {
  "product_id": 3620,
  "is_crop_mark": 0,
  "is_safe_zone": 0,
  "is_ruler": 0,
  "crop_value": 0,
  "safe_value": 0,
  "is_3d_preview": 0,
  "3d_object": "",
  "3d_object_file_upload": "",
  "scale_unit_id": 1,
  "product_image_id": 0,
  "print_profile_ids": [
    175,
    174,
    164
  ],
  "is_variable_decoration": 0,
  "sides": [
    {
      "name": "Side 1",
      "is_visible": 1,
      "image_dimension": "",
      "product_image_side_id": 0,
      "product_decoration": [
        {
          "name": "dd",
          "print_area_id": 14,
          "dimension": {
            "x": 0,
            "y": 0,
            "angle": 0,
            "scaleX": "1.032",
            "scaleY": "1.032"
          },
          "sub_printarea_type": "normal_size",
          "size_variants": [],
          "print_profile_ids": [
            175,
            174,
            164
          ]
        }
      ]
    }
  ]
}
// (Optional): Below thing will Delete the Existing Record and Insert a New Record.
// Additionally you dont need to Delete the old Record
replace: 1

/*
|--------------------------------------------------------------------------
| Create Variable Decoration
|--------------------------------------------------------------------------
 */

Post : http://inkxe-v10.inkxe.io/dev/xetool/api/v1/decorations/1620

'decorations':
{
  "product_id": 2309,
  "is_crop_mark": 0,
  "is_safe_zone": 0,
  "is_ruler": 0,
  "crop_value": 0,
  "safe_value": 0,
  "is_3d_preview": 0,
  "3d_object": "",
  "3d_object_file_upload": "",
  "scale_unit_id": 1,
  "product_image_id": 0,
  "print_profile_ids": [
    164
  ],
  "is_variable_decoration": 1,
  "product_decoration": {
    "is_pre_defined": 0,
    "dimension": {
      "x": 0,
      "y": 0,
      "scaleX": 1,
      "scaleY": 1,
      "angle": 0
    },
    "print_area_id": 7,
    "user_defined_dimensions": {
      "custom_min_width": 3,
      "custom_max_width": 10,
      "custom_max_height": "10",
      "custom_min_height": 3
    },
    "is_border_enable": 0,
    "is_sides_allow": 0
  }
}

// End