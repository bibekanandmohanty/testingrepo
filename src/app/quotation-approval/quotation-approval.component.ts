import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { NotifierService } from 'angular-notifier';
import { DomSanitizer } from '@angular/platform-browser';
import { QuotationService } from './quotation.service';
import Swal from 'sweetalert2';
import { validateFiles } from '../shared/utils/upload-file-validation-utils';
declare var $: any;
declare var require: any;

@Component({
  selector: 'app-quotation-approval',
  templateUrl: './quotation-approval.component.html',
  styleUrls: ['./quotation-approval.component.scss']
})
export class QuotationApprovalComponent implements OnInit {
  @ViewChild('quoteRejectEle', { static: false }) quoteRejectEle: any = ElementRef; // Reject list modal

  public selectedQuoteId: any;
  public quotationDetails: any;
  public productList: Array<any> = [];
  public quoteDetailsLoader!: boolean;
  public quotationSteps: any = [
    {
      id: 1,
      title: 'Quotation',
      isActive: true,
      isComplete: false,
    },
    {
      id: 2,
      title: 'Confirmation',
      isActive: false,
      isComplete: false,
    },
    {
      id: 3,
      title: 'Payment',
      isActive: false,
      isComplete: false,
    },
    {
      id: 4,
      title: 'Complete',
      isActive: false,
      isComplete: false,
    }
  ];
  public lineItemArray: any = [];
  public quoteSetting: any = {};
  public globalSettings: any = {};
  public billingAddress: any = {};
  public shippingAddress: any = {};
  public rejectMessage: any = {};
  public showMakePaymentSection = false;
  public makePaymentForm = false;
  public paymentSuccess = false;
  public paymentMethod = 'paypal';
  public conversationLoader = true;
  public conversationList: any = [];
  public conversationObj: any = {
    message: '',
    file: []
  };
  public isInvalidFile = false;
  public selectedFile = 0;
  public chatLoader = false;
  public paymentOption = 0;
  public paymentLoader = false;
  public preViewArtworkImage = '';
  public lineItemLoader = true;
  public downloadFileFun = require('downloadjs'); // used for download the uploaded files.
  public paymentFailure = false;
  public storeId = '1';
  public isAllowShowView = true;
  constructor(
    private activatedRoute: ActivatedRoute,
    private notifier: NotifierService,
    private sanitizer: DomSanitizer,
    private quoteservice: QuotationService
  ) {
    this.activatedRoute.queryParams.subscribe(params => {
      const token = window.atob(params.token); // Decrypt the url params
      const splitArray = this.splitMulti(token, ['=', '&']); // Separate the url string by =
      //const isStoreId = splitArray.findIndex(ele => ele === 'store_id');
      const isStoreId = splitArray.findIndex(ele => ele.includes('store_id'));
      const isSuccess = splitArray.find(ele => ele === 'success') ? true : false;
      const isFailure = splitArray.find(ele => ele === 'fail') ? true : false;
      this.selectedQuoteId = splitArray[1].split('&')[0]; // Initialize the quotation id from the url string.
      if (isStoreId !== -1) {
        this.storeId = splitArray[isStoreId + 1];
        localStorage.setItem('storeId', this.storeId);
      }
      if (isSuccess) {
        this.makePaymentForm = false;
        this.paymentSuccess = true;
        this.showMakePaymentSection = false;
        this.nextQuoteStep(4, true, true);
      } else if (isFailure) {
        this.makePaymentForm = false;
        this.paymentSuccess = false;
        this.showMakePaymentSection = true;
        this.paymentFailure = true;
      }
    });
  }

  ngOnInit() {
    document.title = 'Quotation Approval';
    this.loadQuoteDetails(this.selectedQuoteId);
  }

  /**
   * Used for decrypt the strings.
   * @param str : string that need to decoded.
   * @param tokens :- Validate according to the token match
   */
  private splitMulti(str, tokens) {
    const tempChar: any = tokens[0]; // We can use the first token as a temporary join character
    for (let i = 1; i < tokens.length; i++) {
      str = str.split(tokens[i]).join(tempChar);
    }
    str = str.split(tempChar);
    return str;
  }
  /**
   * Used for fetching the Quotation Details.
   * @param quoteId :- Contain the quote ID for geting the quotation details.
   */
  loadQuoteDetails(quoteId: any) {
    if (quoteId) {
      this.quoteDetailsLoader = true;
      this.quoteservice.fetchSingleQuoteDetails(quoteId).subscribe(quoteRes => {
        if (quoteRes && quoteRes.status) {
          this.quotationDetails = quoteRes.data[0];
          this.setCustomerBillingAddress();
          this.loadQuoteSetting();
          this.loadGeneralSetting();
          this.quotationDetails.payment_log = [];
          this.quotationDetails.items = [];
          this.lineItemArray = [];
          this.loadLineItems();
          this.loadPaymentLog();
        } else {
          this.isAllowShowView = false;
          Swal.fire('Oops!', quoteRes.message , 'error');
          return;
        }
      }, error => {
        this.notifier.notify('error', 'Failed to load quotation details.');
        this.quoteDetailsLoader = false;
      });
    } else {
      this.notifier.notify('error', 'Invalid quotation id.');
    }
  }

  /**
   * Used for load all the product details.
   */
  loadProductList() {
    this.quotationDetails.items.forEach(qele => {
      if (qele.product_availability !== false) {
        let attributeList: any = [];
        qele.product_variant = qele.product_variant.map(variants => {
          attributeList = [];
          for (const key in variants.attribute) {
            if (variants.attribute.hasOwnProperty(key)) {
              const element = variants.attribute[key];
              const object = {
                attribut_name: key,
                attribute_id: element.attribute_id,
                varinat_name: element.name,
                hex_code: element.hex_code ? element.hex_code : '',
                select_name: element.name,
                variant_id: variants.variant_id,
                variant_list: [],
              };
              attributeList.push({ ...object });
            }
          }
          variants.attributeList = attributeList;
          return variants;
        });
        this.lineItemArray.push({
          id: qele.product_id,
          image: qele.product_store_image.src,
          name: qele.product_name,
          unit_price: qele.product_price,
          design_price: qele.design_cost,
          // files: qele.files.map(fele => fele.file_name),
          isSimpleProduct: (qele.product_variant[0] && qele.product_variant[0].variant_id) ? false : true,
          total_amount: qele.unit_total,
          qty: qele.quantity,
          attributeList,
          isColor: (qele.product_variant[0] && qele.product_variant[0].attribute && qele.product_variant[0].attribute.Color) ? true : false,
          isSize: (qele.product_variant[0] && qele.product_variant[0].attribute && qele.product_variant[0].attribute.Size) ? true : false,
          isMaterial: (qele.product_variant[0] && qele.product_variant[0].attribute && qele.product_variant[0].attribute.Material) ? true : false,
          product_variants: qele.product_variant,
          custom_design_id: qele.custom_design_id,
          uploadDesigns: qele.upload_designs,
          isProductAvailable: qele.product_availability,
          isCustomSize: qele.is_custom_size
        });
      } else {
        this.lineItemArray.push({
          isProductAvailable: qele.product_availability
        });
      }
    });
    this.lineItemLoader = false;
    this.lineItemArray.forEach(lItem => {
      if(lItem && lItem.uploadDesigns.length && lItem.isCustomSize){
        lItem.uploadDesigns.forEach(lDesign => {
          if(lDesign && lDesign.decoration_area.length){
            lDesign.decoration_area.forEach(dArea => {
              if(dArea && dArea.custom_size_dimension){
                dArea.width = Number(dArea.custom_size_dimension.split('x')[0].trim());
                dArea.height = Number(dArea.custom_size_dimension.split('x')[1].trim());
              }
            });
          }
        });
      }
    });

    // this.quoteservice.fetchProductList().subscribe(pRes => {
    //   if (pRes && pRes.status) {
    //     this.productList = pRes.data;
    //     this.lineItemArray = [];
    //     this.productList.forEach(ele => {
    //     });
    //   } else {
    //     this.notifier.notify('error', pRes.message);
    //   }
    //   this.lineItemLoader = false;
    // }, error => {
    //   this.notifier.notify('error', 'Failed to load quotation details.');
    //   this.quoteDetailsLoader = false;
    // });
  }

  /**
   * used for proceed the next step in payment section
   * @param stepId :-contain the stepid which need the modification
   * @param isActive :- if the step is active or not.
   * @param isComplete :- if the step is completed or not.
   */
  nextQuoteStep(stepId: number, isActive: boolean, isComplete: boolean) {
    const index = this.quotationSteps.findIndex(ele => ele.id === stepId);
    this.quotationSteps[index].isActive = true;
    for (let i = 0; i <= index; i++) {
      if (this.quotationSteps[i].id !== stepId) {
        this.quotationSteps[i].isActive = true;
        this.quotationSteps[i].isComplete = true;
      }
    }
  }

  /**
   * Used for  return status show/hide the quotation section form status\
   * @return Boolean status value True/False;
   */
  isShowQuoteApprovalSection() {
    if (this.quotationDetails.quote_status !== 'Approved' && this.quotationDetails.quote_status !== 'Rejected'
      && this.quotationDetails.quote_status !== 'Ordered') {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Used for load the default quote setting list
   */
  loadQuoteSetting() {
    this.quoteservice.fetchQuoteSetting().subscribe(pRes => {
      if (pRes && pRes.status) {
        this.quoteSetting = pRes.data;
      } else {
        console.error('error', pRes.message);
      }
    }, error => {
      console.error('error', 'Failed to fetch the quote settings.');
    });
  }

  /**
   * Used for load the general settings.
   */
  loadGeneralSetting() {
    this.quoteservice.fetchGlobalSetting().subscribe(pRes => {
      if (pRes && pRes.status) {
        this.globalSettings = pRes.general_settings.currency;
      } else {
        console.error('error', pRes.message);
      }
    }, error => {
      console.error('error', 'Failed to fetch the general settings.');
    });
  }

  /**
   * Used for set the customer billing/shipping address
   */
  setCustomerBillingAddress() {
    const { customer: addressObj, shipping_id } = this.quotationDetails;
    const defaultShippingAddress = addressObj.shipping_address ? addressObj.shipping_address.find(address => address.id === shipping_id) : '';
    if (addressObj.billing_address && addressObj.billing_address.country) {
      this.billingAddress = addressObj.billing_address;
      this.billingAddress.country_name = '';
      this.billingAddress.state_name = '';
    } else {
      this.billingAddress = defaultShippingAddress;
    }
    this.shippingAddress = defaultShippingAddress;
  }

  /**
   * Used for return the price element in two decimal number
   * @param flotNum :- Contain the floating number to convert it two descimals.
   */
  convertTwoDescimal(flotNum: any) {
    return parseFloat(flotNum).toFixed(2);
  }

  /**
   * Used for convert the discount or rush amount.
   * @param type: string DISCOUNT/RUSH
   */
  convertPercentageAmount(type = 'string') {
    let convertedAmount = 0;
    const { discount_amount, rush_amount, design_total, discount_type, rush_type, tax_amount, shipping_amount } = this.quotationDetails;
    if (type === 'discount') {
      convertedAmount = design_total * (discount_amount / 100);
    } else if (type === 'rush') {
      convertedAmount = design_total * (rush_amount / 100);
    } else if (type === 'tax') {
      convertedAmount = design_total * (tax_amount / 100);
    }
    return this.convertTwoDescimal(convertedAmount);
  }

  /**
   * Used for return the total payment paid
   */
  getTotalPaidAmount() {
    let sumOfPayment = 0;
    // this.quotationDetails.payment_log.forEach(ele => {
    //   sumOfPayment = sumOfPayment + ele.payment_amount;
    // });
    sumOfPayment = this.quotationDetails.quote_total - this.quotationDetails.due_amount;
    return this.convertTwoDescimal(sumOfPayment);
  }

  /**
   * Used for change the quote status.
   * @param type :-Used for set the quotation type
   */
  changeQuoteStatus(type: string) {
    if (type === 'approved') {
      Swal.fire({
        title: 'Are you sure?',
        text: 'You will not be able to revert it!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve it!',
        cancelButtonText: 'No, keep it'
      }).then((result) => {
        if (result.value) {
          const formData = new FormData();
          formData.append('user_type', 'customer');
          formData.append('status_id', '4');
          formData.append('type', 'approved');
          formData.append('user_id', this.quotationDetails.customer_id);
          this.quoteDetailsLoader = true;
          this.quoteservice.approveQuotation(formData, this.selectedQuoteId).subscribe(pRes => {
            if (pRes && pRes.status === 0) {
              this.notifier.notify('error', pRes.message);
            } else {
              this.notifier.notify('success', 'Quotation approved  successfully.');
              this.loadQuoteDetails(this.selectedQuoteId);
            }
          }, error => {
            this.notifier.notify('error', 'Failed to approve the quotation.');
          });
        }
      });
    } else if (type === 'rejected') {
      this.rejectMessage.message = '';
      this.rejectMessage.loader = false;
    }
  }


  /**
   * Used for check the payment approval section show/hide
   */
  isPaymentApprovalSection() {
    const { quote_status, payment_log, due_amount } = this.quotationDetails;
    if (quote_status === 'Approved' && due_amount !== 0) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Used for reject the quote order
   */
  rejectQuoteOrder() {
    const formData = new FormData();
    formData.append('user_type', 'customer');
    formData.append('quote_id', this.selectedQuoteId);
    formData.append('status_id', '2');
    formData.append('user_id', this.quotationDetails.customer_id);
    formData.append('reject_note', this.rejectMessage.message);
    formData.append('type', 'rejected');
    this.rejectMessage.loader = false;
    this.quoteservice.rejectQuotation(formData).subscribe(pRes => {
      if (pRes && pRes.status === 0) {
        this.notifier.notify('error', pRes.message);
      } else {
        this.notifier.notify('success', 'Quotation rejected successfully.');
        this.loadQuoteDetails(this.selectedQuoteId);
      }
      this.quoteRejectEle.nativeElement.click();
      this.rejectMessage.message = '';
      this.rejectMessage.loader = false;
    }, error => {
      this.notifier.notify('error', 'Failed to reject the quote');
      this.rejectMessage.loader = false;
    });
  }

  /**
   * Used for update payment the payment next step.
   */
  nextPaymentStep() {
    this.showMakePaymentSection = true;
    this.nextQuoteStep(3, true, true);
  }

  /**
   * Used for show the make payment form with the valid data
   */
  setUpmakePaymentForm() {
    this.makePaymentForm = true;
    this.paymentOption = this.quotationDetails.due_amount;
  }

  /**
   * Used for receving the payment.
   */
  confirmPayment() {
    this.paymentLoader = true;
    const formDat = new FormData();
    formDat.append('quote_id', this.selectedQuoteId);
    formDat.append('user_id', this.quotationDetails.customer_id);
    formDat.append('user_type', 'customer');
    formDat.append('payment_amount', String(this.paymentOption));
    this.quoteservice.requestPayment(formDat).subscribe(paymentRes => {
      if (paymentRes && paymentRes.status) {
        window.location.href = paymentRes.url;
      } else {
        this.notifier.notify('error', paymentRes.message);
      }
      // this.paymentLoader = false;
    }, error => {
      this.notifier.notify('error', 'Failed to initiate the payment process. Please try again later.');
      this.paymentLoader = false;
    });
  }
  /**
   * used for hide the success modal
   */
  hideSuccessModal() {
    this.makePaymentForm = false;
    this.paymentSuccess = false;
    this.showMakePaymentSection = false;
  }

  /**
   * Used for hide the  failure modal
   */
  hideFaileModal() {
    this.paymentFailure = false;
  }

  /**
   * Used for load the coversation list .
   * @param quoteId ; contain the quotation id
   */
  loadConversation(quoteId: any) {
    this.quoteservice.fetchConversationDetails(quoteId).subscribe(pRes => {
      if (pRes && pRes.status) {
        this.conversationList = pRes.data;
        setTimeout(() => {
          $('.chat-history').animate({ scrollTop: $('.chat-history').prop('scrollHeight') }, 1000);
        }, 500);
      } else {
        console.error('server-repsonse', pRes.message);
      }
      this.conversationLoader = false;
    }, error => {
      this.notifier.notify('error', 'Failed to load the conversation.');
      this.conversationLoader = false;
    });
  }
  /**
   * Used for handel file upload event
   */
  attachConversationFile() {
    let ele;
    ele = document.getElementById('conver_file_upload') as HTMLElement;
    ele.click();
  }

  /**
   * Used for upload the file
   * @param event :- File handel event .
   */
  handelFileUploadChange(event: any) {
    if (event.target.files.length > 0) {
      for (const file of event.target.files) {
        if (!validateFiles(file.name, 'image') && !validateFiles(file.name, 'svg') && !validateFiles(file.name, 'zip')
          && !validateFiles(file.name, 'pdf')) {
          this.isInvalidFile = true;
          return;
        }
      }
      this.selectedFile = event.target.files.length;
      this.isInvalidFile = false;
      this.conversationObj.file = [];
      for (const key in event.target.files) {
        if (key !== 'length' && key !== 'item') {
          this.conversationObj.file.push(event.target.files[key]);
        }
      }
    }
  }

  /**
   * Used for set the button status disabled or not.
   */
  checkButtonStatus() {
    if (this.conversationLoader || this.isInvalidFile || !this.conversationObj.message || this.chatLoader) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Used for save the conversations and send to the admin end.
   */
  sendMessage() {
    const formData = new FormData();
    formData.append('user_type', 'customer');
    formData.append('quote_id', this.selectedQuoteId);
    formData.append('user_id', this.quotationDetails.customer_id);
    formData.append('message', this.conversationObj.message);
    for (const iterator of this.conversationObj.file) {
      formData.append('upload[]', iterator);
    }
    this.chatLoader = true;
    this.quoteservice.sendInternalNote(formData).subscribe(pRes => {
      if (pRes && pRes.status) {
        this.loadConversation(this.selectedQuoteId);
        $('.chat-history').animate({ scrollTop: $('.chat-history').prop('scrollHeight') }, 1000);
        this.conversationObj.message = '';
        this.conversationObj.file = [];
        this.selectedFile = 0;
      } else {
        this.notifier.notify('error', pRes.message);
      }
      this.chatLoader = false;
    }, error => {
      this.notifier.notify('error', 'Failed to get send  message.');
      this.chatLoader = false;
    });
  }
  /**
   * Used for set the payment price
   */
  setPayment(price: number) {
    this.paymentOption = price;
  }
  /**
   * Used for back to payment section from make payment section
   */
  backToPayment() {
    this.makePaymentForm = false;
  }
  /**
   * Used for set up the conversation settings
   */
  showConversation() {
    this.conversationObj.message = '';
    this.selectedFile = 0;
    this.conversationObj.file = [];
    this.chatLoader = false;
    this.isInvalidFile = false;
    this.loadConversation(this.selectedQuoteId);
  }

  /**
   * Used for download the PDF file.
   */
  downloadPdf() {
    this.quoteservice.downloadQuote(this.selectedQuoteId);
  }

  /**
   * Used for print the quotation page
   */
  printQuotation() {
    window.print(); // Print the page.
  }

  /**
   * Used for set up the uploaded art work preview box
   * @param image :- Contain the preview image that need to display Wide area.
   * @param isFromTool  :- that makes whether it comes from tool so that svg will display accordingly.
   */
  preViewImageSetUp(file: string, isFromTool: boolean) {
    if (isFromTool) {
      this.preViewArtworkImage = '';
      // $('#singl-svg-shower').empty();
      // $('#singl-svg-shower').find('svg').attr({
      //   width: '100%',
      //   class: 'svg-content',
      //   preserveAspectRatio: 'xMidYMid meet'
      // });
      const frameSrc = '<!DOCTYPE html><html><body>' + file + '</body></html>';
      $('#singl-svg-shower').attr('srcdoc', frameSrc);
    } else {
      this.preViewArtworkImage = file;
    }
  }


  /**
   * Parse the images.
   * @param url :- Image Url need to parse
   */
  transform(url) {
    return this.sanitizer.bypassSecurityTrustResourceUrl(url);
  }

  /**
   * Used for download the requested files
   * @param fileUrl: Contain the file URL that needs to be download.
   */
  downloadClickedFile(fileUrl: string) {
    this.downloadFileFun(fileUrl); // Internally Download the files
  }

  /**
   * Used for check the payment log section
   * @return Boolean true/false according to the payment status
   */
  checkpaymentLog() {
    const { payment_log } = this.quotationDetails;
    let paidAmountCount = 0;
    if (payment_log.length === 0) {
      return false;
    } else {
      for (const payment in payment_log) {
        if (payment_log.hasOwnProperty(payment)) {
          const element = payment_log[payment];
          if (element.payment_status !== 'pending') {
            paidAmountCount++;
          }
        }
      }
      return paidAmountCount > 0 ? true : false;
    }
  }

  /**
   * Used for fetch the single product line item details
   * @param quoteId: contain the quote details.
   */
  loadLineItems() {
    this.lineItemLoader = true;
    this.quoteservice.fetchQuoteLineItems(this.selectedQuoteId).subscribe(pRes => {
      if (pRes && pRes.status) {
        this.quotationDetails.items = pRes.data;
        this.loadProductList();
      } else {
        this.notifier.notify('error', pRes.message);
      }
    }, error => {
      this.notifier.notify('error', 'Failed to get the quote line items details.');
    });
  }

  /**
   * Used for load the payment logs
   */
  loadPaymentLog() {
    this.quoteservice.fetchPaymentLogs(this.selectedQuoteId).subscribe(paymentRes => {
      if (paymentRes && paymentRes.status) {
        this.quotationDetails.payment_log = paymentRes.log_data;
        this.quoteDetailsLoader = false;
        if ((this.quotationDetails.quote_status === 'Approved' || this.quotationDetails.quote_status === 'Ordered')
          && this.quotationDetails.due_amount !== 0 && this.quotationDetails.payment_log.length > 0 && this.quoteSetting.is_payment_enable) {
          this.nextQuoteStep(3, true, true);
          this.showMakePaymentSection = true;
        } else if (this.quotationDetails.quote_status === 'Approved' && this.quotationDetails.due_amount !== 0) {
          this.nextQuoteStep(2, true, true);
        } else if ((this.quotationDetails.quote_status === 'Approved' || this.quotationDetails.quote_status === 'Ordered')
          && this.quotationDetails.due_amount === 0) {
          this.nextQuoteStep(4, true, true);
        } else if (this.quotationDetails.quote_status === 'Ordered' && this.quotationDetails.due_amount !== 0
           && this.quoteSetting.is_payment_enable) {
          this.nextQuoteStep(3, true, true);
          this.showMakePaymentSection = true;
        }
      } else {
        this.notifier.notify('error', paymentRes.message);
      }
    }, error => {
      this.notifier.notify('error', 'Failed to get the payment logs.');
    });
  }
}
