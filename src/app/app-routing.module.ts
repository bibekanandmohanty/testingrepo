import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { NotFoundComponent } from './not-found/not-found.component';
import { ArtWorkApprovalComponent } from './art-work-approval/art-work-approval.component';
import { QuotationApprovalComponent } from './quotation-approval/quotation-approval.component';
import { ProductionStatusUpdateComponent } from './production-status-update/production-status-update.component';


const routes: Routes = [
  {
    path: '',
    redirectTo: '/404',
    pathMatch: 'full'
  },
  {
    path: '404',
    component: NotFoundComponent
  },
  {
    path: 'art-work',
    component: ArtWorkApprovalComponent
  },
  {
    path: 'quotation-approval',
    component: QuotationApprovalComponent
  },
  {
    path: 'production-job',
    component: ProductionStatusUpdateComponent
  },
  // Fallback when no prior routes is matched
  { path: '**', redirectTo: '/404', pathMatch: 'full' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes , {
    scrollPositionRestoration: 'enabled',
    anchorScrolling: 'enabled',
    useHash: true,
  })],
  exports: [RouterModule]
})
export class AppRoutingModule { }
