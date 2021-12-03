import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { NotifierService } from 'angular-notifier';
import { ProductionStatusUpdateService } from './production-status-update.service';
import Swal from 'sweetalert2';

@Component({
  selector: 'app-production-status-update',
  templateUrl: './production-status-update.component.html',
  styleUrls: ['./production-status-update.component.scss']
})
export class ProductionStatusUpdateComponent implements OnInit {

  public selectedJobId: any;
  public currentStageId: any;
  public storeId = '1';
  public productionDetails: any;
  public productionLoader = false;
  public isDisplayBtn = false;
  public jobNumber = '';
  public currentStageName = '';
  public updateStatus = '';
  public productionSettingsData: any = {};

  constructor(private activatedRoute: ActivatedRoute,private productionService: ProductionStatusUpdateService, private notifier: NotifierService) {
    
    this.activatedRoute.queryParams.subscribe(params => {
      const token = window.atob(params.token); // Decrypt the url params
      const splitArray = this.splitMulti(token, ['=', '&']); // Separate the url string by =

      // this.selectedJobId = splitArray[1].split('&')[0];
      // this.currentStageId = splitArray[3].split('&')[0];

      // production job id
      const isJobId = splitArray.findIndex(ele => ele.includes('job_id'));
      if (isJobId !== -1) {
        this.selectedJobId = splitArray[isJobId + 1];
      }

      // current stage id
      const isStageId = splitArray.findIndex(ele => ele.includes('current_stage_id'));
      if (isStageId !== -1) {
        this.currentStageId = splitArray[isStageId + 1];
      }

      // store id
      const isStoreId = splitArray.findIndex(ele => ele.includes('store_id'));
      if (isStoreId !== -1) {
        this.storeId = splitArray[isStoreId + 1];
      }
    });
  }

  ngOnInit() {
    document.title = 'Production Status';
    this.loadQuoteSetting();
    this.loadProductionDetails();
  }

  /**
   * Used for decrypt the strings.
   * 
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
   * Fetch production job details.
   */
  loadProductionDetails() {
    if(this.selectedJobId && this.currentStageId) {
      this.productionLoader = true;

      this.productionService.fetchProductionDetails(this.selectedJobId, this.currentStageId, this.storeId).subscribe(prodRes => {
        if (prodRes && prodRes.status) {
          this.productionDetails = prodRes.data;
          if(this.productionDetails && this.productionDetails.production_job) {
            if(this.productionDetails.production_job.show_mark_as_done) {
              this.isDisplayBtn = this.productionDetails.production_job.show_mark_as_done;
            }
            else {
              this.updateStatus = 'already-updated';
            }
            this.jobNumber = '#' + this.productionDetails.production_job.job_id;
            // this.currentStageName = this.productionDetails.production_job.current_stage.status_name;
            this.currentStageName = this.productionDetails.production_job.qr_current_stage;
          }
          
          this.productionLoader = false;
        } else {
          this.productionLoader = false;
          this.notifier.notify('error', 'Failed to load production details.');
        }
      }, error => {
        this.productionLoader = false;
        this.notifier.notify('error', 'Failed to load production details.');
      });
    } else {
      this.productionLoader = false;
      this.notifier.notify('error', 'Invalid job/status id.');
    }
  }

  /**
   * Check whether to display confirmation dialog for production status update or not.
   */
  checkForUpdateConfirmation() {
    if(this.productionSettingsData && this.productionSettingsData.mark_as_done) {
      Swal.fire({
        title: 'Are you sure?',
        text: 'You want to mark it as done!',
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, mark it as done!'
      }).then((result) => {
        if (result.value) {
          this.updateJobStatus();
        }
      });
    }
    else {
      this.updateJobStatus();
    }
  }

  /**
   * Update the selected production job status on button click.
   */
  updateJobStatus() {
    this.updateStatus = '';
    this.productionLoader = true;

    const formData = new FormData();
    formData.append('current_stage_id', String(this.productionDetails.production_job.current_stage_id));
    formData.append('type', 'completed');
    formData.append('user_type', 'agent');
    if(this.productionDetails.production_job.current_stage.assignee_data.length !== 0) {
      formData.append('user_id', this.productionDetails.production_job.current_stage.assignee_data[0].id);
    }
    formData.append('next_stage_id', this.productionDetails.production_job.next_stage_id);
    if (!this.productionDetails.production_job.next_stage_id) {
      formData.append('is_completed', '1');
    }

    this.productionService.updateJobStatus(formData).subscribe(pRes => {
      if (pRes && pRes.status) {
        this.isDisplayBtn = false;
        this.updateStatus = 'success';
      } else {
        this.updateStatus = 'error';
      }
      this.productionLoader = false;
    }, err => {
      this.updateStatus = 'error';
      this.productionLoader = false;
    });
  }

  /**
   * Get production settings data.
   */
   loadQuoteSetting() {
    this.productionService.fetchProductionSetting().subscribe(pRes => {
      if (pRes && pRes.status) {
        this.productionSettingsData = pRes.data;
        // this.productionSettingsData.mark_as_done
      } else {
        console.error('error', pRes.message);
      }
    }, error => {
      console.error('error', 'Failed to fetch the production settings.');
    });
  }
}
