import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { QuotationApprovalComponent } from './quotation-approval.component';

describe('QuotationApprovalComponent', () => {
  let component: QuotationApprovalComponent;
  let fixture: ComponentFixture<QuotationApprovalComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ QuotationApprovalComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(QuotationApprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
