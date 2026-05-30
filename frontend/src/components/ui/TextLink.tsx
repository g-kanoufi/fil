import type { AnchorHTMLAttributes } from 'react';
import { Link, type LinkProps } from 'react-router-dom';
import { cn } from '@/lib/cn';
import { textLink, textLinkPlain } from '@/lib/ui/tokens';

interface TextLinkProps extends LinkProps {
  /** Breadcrumb-style link without font-medium. */
  plain?: boolean;
}

export function TextLink({ plain, className, ...props }: TextLinkProps) {
  return <Link className={cn(plain ? textLinkPlain : textLink, className)} {...props} />;
}

interface ExternalTextLinkProps extends AnchorHTMLAttributes<HTMLAnchorElement> {
  plain?: boolean;
}

export function ExternalTextLink({ plain, className, ...props }: ExternalTextLinkProps) {
  return (
    <a
      className={cn(plain ? textLinkPlain : textLink, className)}
      rel={props.rel ?? 'noreferrer'}
      {...props}
    />
  );
}
