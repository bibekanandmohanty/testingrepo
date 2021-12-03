export function validateFiles(fileName: string, validationType: string) {
  let re: any;
  if (validationType === 'image') {
       re = /(\.jpg|\.jpeg|\.png|\.bmp)$/i;
  } else if (validationType === 'all') {
      re = /(\.jpg|\.jpeg|\.png|\.bmp|\.svg)$/i;
  } else if (validationType === 'jpg&png') {
      re = /(\.jpg|\.jpeg|\.png)$/i;
  } else if (validationType === 'font') {
      re = /(\.ttf)$/i;
  } else if (validationType === 'svg') {
      re =  /(\.svg)$/i ;
  } else if (validationType === 'csv') {
      re =  /(\.csv)$/i ;
  } else if (validationType === 'png') {
      re =  /(\.png)$/i ;
  } else if (validationType === 'zip') {
      re =  /(\.zip)$/i ;
  } else if (validationType === 'pdf') {
      re =  /(\.pdf)$/i ;
  } else if (validationType === 'svg&png') {
      re = /(\.svg|\.png)$/i;
  }
  if (!re.exec(fileName)) {
      return false;
  } else {
      return true;
  }
}
