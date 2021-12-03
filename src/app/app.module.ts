import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { NotFoundComponent } from './not-found/not-found.component';
import { ArtWorkApprovalComponent } from './art-work-approval/art-work-approval.component';
import { NotifierModule, NotifierOptions } from 'angular-notifier';
import { NgxContentLoadingModule } from 'ngx-content-loading';
import { HttpClientModule, HTTP_INTERCEPTORS } from '@angular/common/http';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { LocationStrategy, PathLocationStrategy } from '@angular/common';
import { QuotationApprovalComponent } from './quotation-approval/quotation-approval.component';
import { DateTimeFilterPipe } from './shared/pipes/date-time-filter.pipe';
import { TokenInterceptor } from './Core/interceptor/jwt.interceptor';
import { ProductionStatusUpdateComponent } from './production-status-update/production-status-update.component';

const customNotifierOptions: NotifierOptions = {
  position: {
    horizontal: {
      position: 'right',
      distance: 12
    },
    vertical: {
      position: 'bottom',
      distance: 12,
      gap: 10
    }
  },
  theme: 'material',
  behaviour: {
    autoHide: 5000,
    onClick: 'hide',
    onMouseover: 'pauseAutoHide',
    showDismissButton: true,
    stacking: 4
  },
  animations: {
    enabled: true,
    show: {
      preset: 'slide',
      speed: 300,
      easing: 'ease'
    },
    hide: {
      preset: 'fade',
      speed: 300,
      easing: 'ease',
      offset: 50
    },
    shift: {
      speed: 300,
      easing: 'ease'
    },
    overlap: 150
  }
};
@NgModule({
  declarations: [
    AppComponent,
    NotFoundComponent,
    ArtWorkApprovalComponent,
    QuotationApprovalComponent,
    DateTimeFilterPipe,
    ProductionStatusUpdateComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    NotifierModule.withConfig(customNotifierOptions),
    NgxContentLoadingModule,
    HttpClientModule,
    FormsModule,
    ReactiveFormsModule,

  ],
  providers: [{ provide: LocationStrategy, useClass: PathLocationStrategy },
    { provide: HTTP_INTERCEPTORS, useClass: TokenInterceptor, multi: true }],
  bootstrap: [AppComponent]
})
export class AppModule { }
