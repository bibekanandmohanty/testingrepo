import { Injectable } from '@angular/core';
import { environment } from 'src/environments/environment';
import { HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class ProductionStatusUpdateService {
  public serverApi: string = environment.base_path;
  public parseToken: any = localStorage.getItem('token');

  constructor(private http: HttpClient) { }

 /**
   * Fetch selected production job details.
   * 
   * @param jobId :- current production job id
   * @param stageId :- current production stage id
   * @param storeId :- store id
   */
  fetchProductionDetails(jobId, stageId, storeId) {
    return this.http.get<any>(this.serverApi.concat('productions/job-details/', jobId, '?store_id=',storeId,'&current_stage_id=', stageId));
  }

  /**
   * Update production job status.
   * 
   * @param  formData : Formdata
   */
   updateJobStatus(formData: FormData) {
    return this.http.post<any>(this.serverApi.concat('productions/change-stage'), formData);
  }

  /**
   * Fetch the production settings data.
   */
   fetchProductionSetting() {
    return this.http.get<any>(this.serverApi.concat('production/settings?module_id=4'));
  }
}
