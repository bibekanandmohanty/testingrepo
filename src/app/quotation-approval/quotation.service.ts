import { Injectable } from '@angular/core';
import { environment } from 'src/environments/environment';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class QuotationService {
  public serverApi: string = environment.base_path;
  public parseToken: any = localStorage.getItem('token');
  public storeId: any = '1';
  constructor(
    private http: HttpClient
  ) { }

  /**
   * Used for fetch the quotation-detail.
   * @param quoteId : Contain the quotation ID
   */
  fetchSingleQuoteDetails(quoteId: any) {
    return this.http.get<any>(this.serverApi.concat('quotation/', quoteId));
  }

  /**
   * used for fetch product list
   */
  fetchProductList() {
    return this.http.get<any>(this.serverApi.concat('products?quote=1'));
  }

  /**
   * Used for fetch the quote setting details.
   */
  fetchQuoteSetting() {
    return this.http.get<any>(this.serverApi.concat('production/settings?module_id=1'));
  }

  /**
   * Used for get all the globale settings related Data's
   */
  fetchGlobalSetting() {
    return this.http.get<any>(this.serverApi.concat('settings'));
  }

  /**
   * Used for approve the quotation
   */
  approveQuotation(formData: FormData , quoteId: any) {
    return this.http.post<any>(this.serverApi.concat('quotation/status/', quoteId), formData);
  }

  /**
   * Used for reject the quotation list details
   * @param formdata :- Contain the quotation details form objects
   */
  rejectQuotation(formData: FormData) {
    return this.http.post<any>(this.serverApi.concat('quotation/reject'), formData);
  }

  /**
   * Used for fetch the conversation list from api
   * @param quoteId :- Used for fetch the conversation list.
   */
  fetchConversationDetails(quoteId: any) {
    return this.http.get<any>(this.serverApi.concat('quotation/conversation/', quoteId));
  }

  /**
   * used for send the internal conversation between customer and admin
   * @param formData : Contain the quote form details
   */
  sendInternalNote(formData: FormData) {
    return this.http.post<any>(this.serverApi.concat('quotation/conversation'), formData);
  }

  /**
   * Used request paypal payment
   * @param quoteObj :- contain the amount thats need to be paid
   */
  requestPayment(quoteObj) {
    return this.http.post<any>(this.serverApi.concat('quotation-payment/paypal-payment'), quoteObj);
  }

  /**
   * Used for download the quotation
   * @param quoteId :- contain the quotation that's need to download.
   */
  downloadQuote(quoteId: any) {
    this.parseToken = (this.parseToken === null || !this.parseToken) ? localStorage.getItem('token') : this.parseToken;
    this.storeId = localStorage.getItem('storeId') ? localStorage.getItem('storeId') : '1';
    window.open(
      this.serverApi.concat('quotation/download/', quoteId, '?_token=' , this.parseToken, '&store_id=', this.storeId),
      '_self' // <- This is what makes it open in a new window.
    );
  }

  /**
   * Used for fetch all the line items details
   * @param quoteId Quotation Id Used for get the line items
   */
  fetchQuoteLineItems(quoteId: any) {
    return this.http.get<any>(this.serverApi.concat('quotation/item-list/', quoteId));
  }

  /**
   * Used for getting the payment logs.
   * @param quoteId : Quotation id that used for fetch the payment ralated to that quotation
   */
  fetchPaymentLogs(quoteId: any) {
    return this.http.get<any>(this.serverApi.concat('quotation-payment/log/', quoteId));
  }
}
