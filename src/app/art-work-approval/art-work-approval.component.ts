import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { NotifierService } from 'angular-notifier';
import { ArtWorkApprovalService } from './art-work-approval.service';
import { validateFiles } from '../../app/shared/utils/upload-file-validation-utils';
import { DomSanitizer } from '@angular/platform-browser';
import { convertImageToBase64 } from '../../app/shared/utils/image-utils';
import Swal from 'sweetalert2';
import * as moment from 'moment';
import { environment } from 'src/environments/environment';
declare var require: any;
import { getrevdata } from '../shared/utils/licence-decrypt';
@Component({
  selector: 'app-art-work-approval',
  templateUrl: './art-work-approval.component.html',
  styleUrls: ['./art-work-approval.component.scss']
})
export class ArtWorkApprovalComponent implements OnInit {

  public selectedOrderId: any;
  public orderDetailsData: any;

  public orderLogs: any = [];

  public decorationSettingToggle: any = [] ;
  public artWorkMessage = '';
  public orderLoader = true;
  public isPostLoader = false;
  public isValidFile = false;
  public message = '';
  public errorText = false;
  public isShowRejectBox: any = [];
  public artWorkList: any;
  public toggleTextbox = true;
  public downloadFileFun = require('downloadjs');
  public isProductionIncluded = false;
  public storeId: any = '1';
  constructor(
    private activatedRoute: ActivatedRoute,
    private notifier: NotifierService,
    private orderService: ArtWorkApprovalService,
    private sanitizer: DomSanitizer,

    ) {
       this.activatedRoute.queryParams.subscribe(params => {
          const token = window.atob(params.token); // Decrypt the url params
          const splitArray = token.split('='); // Separate the url string by =
          const workType = params.type; // Get the type of the url params requesting.
          this.selectedOrderId = splitArray[1].split('&')[0]; // Initialize the order id from the url string.
          this.storeId = splitArray[2] ? splitArray[2] : '1';
          localStorage.setItem('storeId', this.storeId);
        });
      }


      ngOnInit() {
        this.getOrderDetails();
        const licenseStr = getrevdata(environment.licence_key);
        const moduleList: any = licenseStr.split('|').pop();
        if (moduleList.includes('productionManagement')) {
          this.isProductionIncluded = true;
        }
      }

      /**
       * Call service to fetch the order details.
       */
      getOrderDetails() {
        // this.loader.startLoading();
        if (!this.isPostLoader) {
          this.orderLoader = true;
        }
        this.orderService.getOrderDetails(this.selectedOrderId).subscribe(res => {
          if (res.status === 1) {
            this.orderDetailsData = res.data;
            this.decorationSettingToggle = new Array(this.orderDetailsData.orders.length);
            this.getOrderLogList('init');
          } else {
            this.orderLoader = false;
            this.notifier.notify('error', res.message);
          }

        }, err => {
          this.orderLoader = false;
          this.notifier.notify('error', 'Error while fetching order details.');
        });
      }

      // ------------------------------------ACTIVITY LOG VIEW SECTION------------------------------
      /**
       * Call service to fetch the order logs.
       * @param callType - Method calling scope
       */
      getOrderLogList(callType: string = 'other_method') {
        if (callType !== 'init' && !this.isPostLoader) {
          // this.loader.startLoading();
          this.orderLoader = true;
        }
        this.isPostLoader = false;
        this.orderService.getOrderLogList(this.selectedOrderId).subscribe(res => {
          if (res.status === 1) {
            this.orderLogs = this.groupByMonth(JSON.parse(JSON.stringify(res.data)), 'created_at');
            let tempIndex = false;
            let artWorkStatus = true;
            this.isShowRejectBox = new Array(this.orderLogs[0].logs.length);
            this.orderLogs[0].logs.forEach((orderEle, cKey) => {
              this.isShowRejectBox[cKey] = false;
              if (orderEle.artwork_status === 'rejected' || orderEle.artwork_status === 'approved') {
                artWorkStatus = false;
              }
              if (orderEle.log_type === 'artwork' && orderEle.files.length > 0 && !tempIndex && artWorkStatus) {
                tempIndex = true;
                this.isShowRejectBox[cKey] = true;
              }
            });
            this.orderLoader = false;
          } else {
            this.orderLogs = [];
            this.orderLoader = false;
            this.notifier.notify('error', 'Error while fetching order logs.');
          }

        }, err => {
          this.orderLogs = [];
          this.orderLoader = false;
          this.notifier.notify('error', 'Error while fetching order logs.');
        });
      }

      /**
       * Group the logs according to the Month and Year.
       * @param arr - Fetched Order Log from API
       * @param key - Attribute name of the object to be grouped accordingly
       */
      groupByMonth(arr: Array<any>, key: string) {
        const monthNameArr = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August',
          'September', 'October', 'November', 'December'];
        const groupedArr = arr.reduce((result, currentValue) => {
          // If an array already present for key, push it to the array. Else create an array and push the object
          const monthNumber = moment(currentValue[key]).format('M');
          const monthName = monthNameArr[Number(monthNumber) - 1];
          const currentKeyValue = monthName + ', ' + moment(currentValue[key]).format('YYYY');

          if (!result[currentKeyValue]) {
            result[currentKeyValue] = [];
          }
          result[currentKeyValue].push(currentValue);
          // Return the current iteration `result` value, this will be taken as next iteration `result` value and accumulate
          return result;
        }, {}); // empty object is the initial value for result object

        // this will return an array of objects, each object containing a group of objects
        return Object.keys(groupedArr).map(key => ({ month: key, logs: groupedArr[key] }));
      }

      /**
       * toggle decoration settings button to - view more/view less
       */
      changeDecorationView(index) {
        this.decorationSettingToggle[index] = !this.decorationSettingToggle[index] ?   false : true;
        this.decorationSettingToggle[index] = !this.decorationSettingToggle[index];
      }

      /**
       * Used for set the artworkfile for uploading.
       */
      onSelectArtworkFile(event) {
        if (event.target.files.length > 0) {
          this.isValidFile = false;
          for (const file of event.target.files) {
              if (!validateFiles(file.name, 'image') && !validateFiles(file.name, 'svg') && !validateFiles(file.name, 'zip')
              && !validateFiles(file.name, 'pdf')) {
                this.message = 'Invalid File Type';
                this.isValidFile = true;
                return ;
              }
            }
          this.artWorkList.imageArray = [];
          this.message   = '';
          this.artWorkList.previewArray = [];
          this.isValidFile = false;
          for (const key in event.target.files) {
            if (key !== 'length' && key !== 'item') {
              this.artWorkList.imageArray.push(event.target.files[key]);
              if (event.target.files[key].name.split('.').pop() === 'zip' || event.target.files[key].name.split('.').pop() === 'ZIP') {
                this.artWorkList.previewArray.push('assets/images/zip.png');
              } else if (event.target.files[key].name.split('.').pop() === 'pdf' ||
              event.target.files[key].name.split('.').pop() === 'PDF') {
                this.artWorkList.previewArray.push('assets/images/pdf.png');
              } else {
                const fileData = event.target.files[key] as File;
                const reader = new FileReader();
                convertImageToBase64(reader, fileData)
                  .subscribe(base64image => {
                    this.artWorkList.previewArray.push(this.sanitizer.bypassSecurityTrustResourceUrl(base64image));
                });
              }
            }
          }
        }
      }

      /**
       * Used for remove image and preview array from list .
       * @param index : Used for remove the image from the array list
       */
      removeImage(index) {
        this.artWorkList.previewArray.splice(index, 1);
        this.artWorkList.imageArray.splice(index, 1);
      }

      /**
       * Used for send the details to customer
       * @param type :- used for distinguies the artwork type status
       * @param convId :- Consversation Id
       * @param orderId :- Order Id
       */
      sendArtwork(type: string, convId: any, orderId: any) {
        const formData = new FormData();
        const logDataParam: any = {};
        if (type === 'reject') {
          this.errorText = false;
          logDataParam.order_id = orderId;
          logDataParam.agent_type = 'customer';
          logDataParam.agent_id = this.orderDetailsData.customer_id;
          logDataParam.message = String(this.message.trim());
          logDataParam.log_type = 'artwork';
          logDataParam.artwork_status = 'rejected';
          logDataParam.status = 'new';
          logDataParam.files = [];
          this.isPostLoader = true;
          formData.append('log_data', JSON.stringify(logDataParam));
          logDataParam.artwork_status = 'rejected';
          this.orderService.sendArtWork(formData).subscribe(orderRes => {
            if (orderRes && orderRes.status === 1) {
              this.isShowRejectBox = [];
              this.message = '';
              this.toggleTextbox = false;
            } else {
              this.notifier.notify('error',  'Failed to send artwork proofs.');
              this.isPostLoader = false;
            }
          }, error => {
            this.notifier.notify('error',  'Failed to send artwork proofs.');
            this.isPostLoader = false;
          });
        } else if (type === 'approve') {
          logDataParam.artwork_status = 'approved';
          Swal.fire({
            title: 'Are you sure?',
            text: 'You will not be able to revert it!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Approve it!',
            cancelButtonText: 'No, keep it'
          }).then((result) => {
            if (result.value) {
              logDataParam.order_id = orderId;
              logDataParam.agent_type = 'customer';
              logDataParam.agent_id = this.orderDetailsData.customer_id;
              logDataParam.message = '';
              logDataParam.log_type = 'artwork';
              logDataParam.artwork_status = 'approved';
              logDataParam.status = 'new';
              logDataParam.files = [];
              this.isPostLoader = true;
              formData.append('log_data', JSON.stringify(logDataParam));
              this.orderService.sendArtWork(formData).subscribe(orderRes => {
                if (orderRes && orderRes.status === 1) {
                  this.isShowRejectBox = [];
                  this.message = '';
                  this.toggleTextbox = false;
                } else {
                  this.notifier.notify('error',  'Failed to send artwork proofs.');
                  this.isPostLoader = false;
                }
              }, error => {
                this.notifier.notify('error',  'Failed to send artwork proofs.');
                this.isPostLoader = false;
              });
              this.errorText = false;
              logDataParam.order_id = orderId;
              logDataParam.conversation_id = convId;
              logDataParam.log_type = 'artwork';
              this.isPostLoader = true;
              formData.append('conversation_id', logDataParam.conversation_id);
              formData.append('artwork_status', logDataParam.artwork_status);
              this.orderService.toggleArtworkStatus(formData, orderId).subscribe(orderRes => {
                if (orderRes && orderRes.status === 1) {
                  if (orderRes.is_create_production_job && this.isProductionIncluded) {
                    Swal.fire({
                      text: `Please do not refresh your broswer !`,
                      allowEscapeKey: false,
                      allowOutsideClick: false,
                      onOpen: () => {
                        Swal.showLoading();
                      }
                    });
                    const orderData = new FormData();
                    orderData.append('order_id', `[${orderRes.order_id}]`);
                    orderData.append('user_type', 'customer' );
                    orderData.append('user_id', this.orderDetailsData.customer_id);
                    this.orderService.generateNewJobCard(orderData).subscribe(pRes => {
                      if (pRes && pRes.status) {
                        this.notifier.notify('success', 'Production job card created successfully.');
                        const jobData = new FormData();
                        jobData.append('email_data', JSON.stringify([pRes.email_data]));
                        this.orderService.sendEmailAgents(jobData);
                      } else {
                        this.notifier.notify('error', pRes.messsage);
                      }
                      Swal.close();
                    }, error => {
                      // this.notifier.notify('error', 'Something went wrong during create a production!. Please try again later.');
                      Swal.close();
                    });
                  }
                  this.getOrderLogList();
                  this.notifier.notify('success', 'Artwork posted successfully.');
                } else {
                  this.notifier.notify('error',  'Failed to send artwork proofs.');
                  this.isPostLoader = false;
                }
              }, error => {
                this.notifier.notify('error',  'Failed to send artwork proofs.');
                this.isPostLoader = false;
              });
            }
          });
          return;
        }
        this.errorText = false;
        logDataParam.order_id = orderId;
        logDataParam.conversation_id = convId;
        logDataParam.log_type = 'artwork';
        this.isPostLoader = true;
        formData.append('conversation_id', logDataParam.conversation_id);
        formData.append('artwork_status', logDataParam.artwork_status);

        this.orderService.toggleArtworkStatus(formData, orderId).subscribe(orderRes => {
          if (orderRes && orderRes.status === 1) {
            this.getOrderLogList();
            this.notifier.notify('success', 'Artwork posted successfully.');
          } else {
            this.notifier.notify('error',  'Failed to send artwork proofs.');
            this.isPostLoader = false;
          }
        }, error => {
          this.notifier.notify('error',  'Failed to send artwork proofs.');
          this.isPostLoader = false;
        });
      }

      showRejctForm(index) {
        this.toggleTextbox = !this.toggleTextbox;
      }

      removeHtml(str: any) {
        if ((str === null) || (str === '')) {
          return ;
        } else {
          str = str.toString();
        }
        return str.replace( /(<([^>]+)>)/ig, '');
      }

      /**
       * Used for download the requested files
       * @param fileUrl: Contain the file URL that needs to be download.
       */
      downloadClickedFile(fileUrl: string) {
        this.downloadFileFun(fileUrl); // Internally Download the files
      }
}
