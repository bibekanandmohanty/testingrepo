import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { environment } from '../../environments/environment';


@Injectable({
  providedIn: 'root'
})
export class ArtWorkApprovalService {
  public serverApi: string = environment.base_path;

  constructor(
    private http: HttpClient
  ) { }

  /**
   * Fetch details of the selected order.
   * @param orderId - id of the selected order
   */
  public getOrderDetails(orderId) {
    return this.http.get<any>(this.serverApi.concat('orders/', orderId));
  }

  /**
   * Fetch the Activity Log List of the selected Order
   * @param orderId - Selected Order Id
   */
  public getOrderLogList(orderId: string) {
    return this.http.get<any>(this.serverApi.concat('order-logs/', orderId) + '?type=customer');
  }

  /**
   * Used for sending the artwork data to the customer end
   */
  sendArtWork(formData: FormData) {
    return this.http.post<any>(this.serverApi.concat('order-logs'), formData);
  }

  /**
   * Used for sending the artwork data to the customer end
   */
  toggleArtworkStatus(formData: FormData, orderId) {
    return this.http.post<any>(this.serverApi.concat('order-artwork-status/', orderId), formData);
  }

  generateNewJobCard(formData: FormData) {
    return this.http.post<any>(this.serverApi.concat('productions/create-job'), formData);
  }

 /**
  * Used for change the expection completed date.
  * @param formData :- Contain the form details
  */
  sendEmailAgents(formData: FormData) {
    this.http.post<any>(this.serverApi.concat('productions/send-email'), formData).subscribe((res) => {
    });
  }
}
